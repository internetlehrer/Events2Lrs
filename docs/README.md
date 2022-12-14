# Documentation

## Test Pass Event Tracking
The following test events are tracked by this plugin: (Note `xAPI Verb`)
- Start New Test Pass - `attempted`
- Suspend the Test - `suspended`
- Resume the Test - `resumed`
- Finish the Test - `completed`

On each event an xAPI Statement is generated and sent to the configured learning record store (LRS). The statements are built using the `ilTst2LrsXapiStatement` class.

In the special case of test completition, `result: success` and additional statements for the individual question responses are included aswell.

## Test Response Tracking
When a test is submitted, additional xAPI question response statements are generated for the following fully-supported question types:
- `assSingleChoice`
- `assMultipleChoice`
- `assTextQuestion`
- `assNumeric`

These statements include data such as scoring, submitted answers, solutions, the test question and more.

For non-supported-types, submitted answers and solutions are not included.

## Adding support for new question types
Support for new question types can be added by adding the type to `ilTst2LrsXapiTestResponseStatement::$INTERACTION_TYPES` and then extending the logic in the `ilTst2LrsXapiTestResponseStatement` class.

## Future Work
- Adding support for more question types
- Integrate question feedback
- Support question hints