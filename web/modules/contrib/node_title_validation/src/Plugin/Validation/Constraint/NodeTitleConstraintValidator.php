<?php

namespace Drupal\node_title_validation\Plugin\Validation\Constraint;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Validates the NodeTitleValidate constraint.
 */
class NodeTitleConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('string_translation')
    );
  }

  /**
   * Constructs a new NodeTitleConstraintValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Config factory service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation.
   */
  public function __construct(EntityTypeManager $entityTypeManager, ConfigFactory $configFactory, TranslationInterface $stringTranslation) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    // Make sure the field is not empty.
    if ($items->isEmpty()) {
      return;
    }

    // Get the user entered title.
    $value_title = $items->value;
    if (empty($value_title)) {
      return;
    }

    // Get host node.
    $node = $items->getEntity();
    if (!$node instanceof NodeInterface) {
      return;
    }

    // Get host node type.
    $node_type = $node->getType();
    if (empty($node_type)) {
      return;
    }

    // Check if module config exists.
    $node_title_validation_config = $this->configFactory
      ->getEditable('node_title_validation.node_title_validation_settings')
      ->get('node_title_validation_config');
    if (empty($node_title_validation_config)) {
      return;
    }

    $title = explode(' ', $value_title);
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    // Add a comma if comma is blacklist.
    $exclude_comma = [];
    if (!empty($node_title_validation_config['comma-' . $node_type])) {
      $exclude_comma[] = ',';
    }
    // Get exclude values for current content type.
    $type_exclude = isset($node_title_validation_config['exclude-' . $node_type]) ? $node_title_validation_config['exclude-' . $node_type] : '';

    if (!empty($type_exclude) || $exclude_comma) {
      // Replace \r\n with comma.
      $type_exclude = str_replace("\r\n", ',', $type_exclude);
      // Store into array.
      $type_exclude = explode(',', $type_exclude);

      $type_exclude = array_merge($type_exclude, $exclude_comma);

      // Find any exclude value found in node title.
      $findings = _node_title_validation_search_excludes_in_title($value_title, $type_exclude);

      if ($findings) {
        $message = $this->t("This characters/words are not allowed to enter in the title - @findings", ['@findings' => implode(', ', $findings)]);
        $this->context->addViolation($message);
      }
    }

    $include_comma = [];
    foreach ($node_title_validation_config as $config_key => $config_value) {
      if ($config_value && $config_key == 'comma-' . $node_type) {
        $include_comma[] = ',';
      }
      if ($config_key == 'exclude-' . $node_type || $include_comma) {
        if (!empty($config_value)) {
          $config_values = array_map('trim', explode(',', $config_value));
          $config_values = array_merge($config_values, $include_comma);
          $findings = [];
          foreach ($title as $title_value) {
            if (in_array(trim($title_value), $config_values)) {
              $findings[] = $title_value;
            }
          }
          if ($findings) {
            $message = $this->t("These characters/words are not permitted in the title - @findings", ['@findings' => implode(', ', $findings)]);
            $this->context->addViolation($message);
          }
        }
      }
      if ($config_key == 'min-' . $node_type) {
        if (mb_strlen($value_title) < $config_value) {
          $message = $this->t("Title should have a minimum @config_value character(s)", ['@config_value' => $config_value]);
          $this->context->addViolation($message);
        }
      }
      if ($config_key == 'max-' . $node_type) {
        if (mb_strlen($value_title) > $config_value) {
          $message = $this->t("Title should not exceed @config_value character(s)", ['@config_value' => $config_value]);
          $this->context->addViolation($message);
        }
      }
      if ($config_key == 'min-wc-' . $node_type) {
        if (count(explode(' ', $value_title)) < $config_value) {
          $message = $this->t("Title should have a minimum word count of @config_value", ['@config_value' => $config_value]);
          $this->context->addViolation($message);
        }
      }
      if ($config_key == 'max-wc-' . $node_type) {
        if (count(explode(' ', $value_title)) > $config_value) {
          $message = $this->t("Title should not exceed a word count of @config_value", ['@config_value' => $config_value]);
          $this->context->addViolation($message);
        }
      }
      if ($config_key == 'unique-' . $node_type || $config_key == 'unique') {
        if ($config_value == 1) {
          // Unique node title for all content types('unique')
          $properties = ['title' => $value_title];
          if ($config_key == 'unique-' . $node_type) {
            // Unique node title for one content type('unique-')
            $properties['type'] = $node_type;
          }
          $nodes = $nodeStorage->loadByProperties($properties);
          // Remove current node form list
          if (isset($nodes[$node->id()])) {
            unset($nodes[$node->id()]);
          }
          // Show error.
          if (!empty($nodes)) {
            $message = $this->t("The title must be unique. Other content is already using this title: @title", ['@title' => $value_title]);
            $this->context->addViolation($message);
          }
        }
      }
    }
  }

}

/**
 * Helper function to find any exclude values in node title.
 */
function _node_title_validation_search_excludes_in_title($input, array $find) {
  $findings = [];
  // Finding characters in the node title.
  foreach ($find as $char) {
    // Check for single character.
    if (mb_strlen(trim($char)) == 1) {
      if (strpos($input, trim($char)) !== FALSE) {
        $characters = $char == ',' ? '<b>,</b>' : trim($char);
        $findings[] = $characters;
      }
    }
  }

  // Finding words in the node title.
  $words = explode(' ', $input);
  if (!empty($find)) {
    $find = array_map('trim', $find);
  }
  foreach ($words as $word) {
    if (mb_strlen(trim($word)) > 1) {
      if (in_array($word, $find)) {
        $findings[] = $word;
      }
    }
  }

  return $findings;
}
