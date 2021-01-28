UPDATING TO 8.x-3.x FROM 8.x-2.x
--------------------------------

When updating to 8.x-3.x from 8.x-2.x, there is no automated upgrade path. Site owners will need to manually verify and update their site configuration. In most cases, only minor changes will be required.

The difference between the two branches is that 8.x-3.x supports JSON objects and arrays and 8.x-2.x does not. As a result, the config values must now be formatted as valid JSON.

## Examples

### Boolean
There is no change for boolean values.

Before
`forcePasteAsPlainText = true`

After
`forcePasteAsPlainText = true`

### Number
There is no change for number values.

Before
`tabIndex = 3`

After
`tabIndex = 3`

### String
String values must now be encapsulated in quotes.

Before
`removePlugins = font`

After
`removePlugins = "font"`

### Object/Array
JSON objects and arrays were not supported in 8.x-2.x. JSON objects and arrays must be formatted to a single line.

Before
N/A

After
`format_h2 = { "element": "h2", "attributes": { "class": "contentTitle2" } }`
