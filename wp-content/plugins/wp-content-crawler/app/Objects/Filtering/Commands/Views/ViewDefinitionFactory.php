<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 27/06/2020
 * Time: 08:16
 *
 * @since 1.11.0
 */

namespace WPCCrawler\Objects\Filtering\Commands\Views;

use WPCCrawler\Objects\Filtering\Enums\InputName;
use WPCCrawler\Objects\Views\CheckboxWithLabel;
use WPCCrawler\Objects\Views\Enums\ViewVariableName;
use WPCCrawler\Objects\Views\InputWithLabel;
use WPCCrawler\Objects\Views\MultipleCookieWithLabel;
use WPCCrawler\Objects\Views\MultipleInputWithLabel;
use WPCCrawler\Objects\Views\MultipleRequestHeaderWithLabel;
use WPCCrawler\Objects\Views\MultipleSelectorWithLabel;
use WPCCrawler\Objects\Views\NumericInputWithLabel;
use WPCCrawler\Objects\Views\Select\SelectElementLocationWithLabel;

class ViewDefinitionFactory {

    /** @var null|ViewDefinitionFactory */
    private static $instance = null;

    /**
     * @return ViewDefinitionFactory
     * @since 1.11.0
     */
    public static function getInstance(): ViewDefinitionFactory {
        if (self::$instance === null) {
            self::$instance = new ViewDefinitionFactory();
        }

        return self::$instance;
    }

    /** This is a singleton */
    protected function __construct() { }

    /**
     * @return ViewDefinition Multiple selector setting that will be used to enter multiple CSS selectors. The input
     *                        name is {@link InputName::CSS_SELECTOR}.
     * @since 1.11.0
     */
    public function createMultipleCssSelectorInput(): ViewDefinition {
        return (new ViewDefinition(MultipleSelectorWithLabel::class))
            ->setVariable(ViewVariableName::TITLE, _wpcc('Selectors'))
            ->setVariable(ViewVariableName::NAME,  InputName::CSS_SELECTOR)
            ->setVariable(ViewVariableName::INFO,  _wpcc('Enter one or more selectors that will be used to find the target element(s).'));
    }

    /**
     * @return ViewDefinition A text input into which an attribute name of an element should be entered. The input name
     *                        is {@link InputName::ELEMENT_ATTR}.
     * @since 1.11.0
     */
    public function createElementAttributeInput(): ViewDefinition {
        return (new ViewDefinition(InputWithLabel::class))
            ->setVariable(ViewVariableName::TITLE, _wpcc('Attribute name'))
            ->setVariable(ViewVariableName::INFO,  _wpcc('Name of an attribute of the element(s)'))
            ->setVariable(ViewVariableName::NAME,  InputName::ELEMENT_ATTR)
            ->setVariable(ViewVariableName::TYPE,  'text');
    }

    /**
     * @return ViewDefinition A multiple text input into which a mathematical formula should be entered. The input name
     *                        is {@link InputName::FORMULA}.
     * @since 1.11.0
     */
    public function createFormulaInput(): ViewDefinition {
        return (new ViewDefinition(MultipleInputWithLabel::class))
            ->setVariable(ViewVariableName::TITLE, _wpcc('Formula'))
            ->setVariable(
                ViewVariableName::INFO,
                sprintf(
                    _wpcc('Enter the formulas that will be used to create the new value. If you enter more than 
                    one, a random one will be used. Use "%1$s" or "%2$s" to include the original value. For example, you 
                    can write %3$s to multiply the number by %4$s. You can use the following operators: %5$s'),
                    'x',
                    'X',
                    '<b>x * 3^2</b>',
                    '<b>9</b>',
                    '<b>*, +, /, -, ^</b>'
                )
            )
            ->setVariable(ViewVariableName::NAME, InputName::FORMULA)
            ->setVariable(ViewVariableName::PLACEHOLDER, _wpcc('Formula'))
            ->setVariable(ViewVariableName::TYPE, 'text');
    }

    /**
     * @return ViewDefinition A checkbox input which should be checked only if the text inside an HTML code should be
     *                        considered, not the HTML code itself. The input name is {@link InputName::TREAT_AS_HTML}.
     * @since 1.11.0
     */
    public function createTreatAsHtmlInput(): ViewDefinition {
        return (new ViewDefinition(CheckboxWithLabel::class))
            ->setVariable(ViewVariableName::TITLE, _wpcc('Treat as HTML?'))
            ->setVariable(ViewVariableName::INFO,  _wpcc('Check this if the subject should be treated as HTML and 
                only the texts in the HTML code should be considered, not the HTML code itself.'))
            ->setVariable(ViewVariableName::NAME,  InputName::TREAT_AS_HTML);
    }

    /**
     * @return ViewDefinition A text input into which a test string should be entered. The input name is
     *                        {@link InputName::TEST_TEXT}.
     * @since 1.11.0
     */
    public function createTestTextInput(): ViewDefinition {
        return (new ViewDefinition(InputWithLabel::class))
            ->setVariable(ViewVariableName::TITLE, _wpcc('Test text'))
            ->setVariable(ViewVariableName::INFO,  _wpcc('Enter a test text'))
            ->setVariable(ViewVariableName::NAME,  InputName::TEST_TEXT)
            ->setVariable(ViewVariableName::TYPE,  'text');
    }

    /**
     * @return ViewDefinition A text input into which a test number should be entered. The input name is
     *                        {@link InputName::TEST_NUMBER}.
     * @since 1.11.0
     */
    public function createTestNumberInput(): ViewDefinition {
        return (new ViewDefinition(NumericInputWithLabel::class))
            ->setVariable(ViewVariableName::TITLE, _wpcc('Test number'))
            ->setVariable(ViewVariableName::INFO,  _wpcc('Enter a test number'))
            ->setVariable(ViewVariableName::NAME,  InputName::TEST_NUMBER)
            ->setVariable(ViewVariableName::STEP,  'any');
    }

    /**
     * @return ViewDefinition A text input into which a test date should be entered. The input name is
     *                        {@link InputName::TEST_DATE}.
     * @since 1.11.0
     */
    public function createTestDateInput(): ViewDefinition {
        return (new ViewDefinition(InputWithLabel::class))
            ->setVariable(ViewVariableName::TITLE, _wpcc('Test date'))
            ->setVariable(ViewVariableName::INFO,  _wpcc_enter_date_with_format())
            ->setVariable(ViewVariableName::NAME,  InputName::TEST_DATE)
            ->setVariable(ViewVariableName::TYPE,  'text');
    }

    /**
     * @return ViewDefinition A multi-input that is used to define cookies. The input name is {@link InputName::COOKIES}.
     * @since 1.14.0
     */
    public function createCookiesInput(): ViewDefinition {
        return (new ViewDefinition(MultipleCookieWithLabel::class))
            ->setVariable(ViewVariableName::TITLE, _wpcc('Extra cookies'))
            ->setVariable(ViewVariableName::INFO,  _wpcc('Enter the extra cookies that will be sent with the request'))
            ->setVariable(ViewVariableName::NAME,  InputName::COOKIES);
    }

    /**
     * @return ViewDefinition A multi-input that is used to define request headers. The input name is
     *                        {@link InputName::REQUEST_HEADERS}.
     * @since 1.14.0
     */
    public function createRequestHeadersInput(): ViewDefinition {
        return (new ViewDefinition(MultipleRequestHeaderWithLabel::class))
            ->setVariable(ViewVariableName::TITLE, _wpcc('Extra request headers'))
            ->setVariable(ViewVariableName::INFO,  _wpcc('Enter the extra headers that will be sent with the request'))
            ->setVariable(ViewVariableName::NAME,  InputName::REQUEST_HEADERS);
    }

    /**
     * @return ViewDefinition A select element that shows the available locations for moving an element to
     * @since 1.14.0
     */
    public function createElementLocationSelect(): ViewDefinition {
        return (new ViewDefinition(SelectElementLocationWithLabel::class))
            ->setVariable(ViewVariableName::TITLE, _wpcc('Location'))
            ->setVariable(ViewVariableName::INFO,  _wpcc('Select the location of the new HTML element
                relative to the reference element, which is the element specified via its CSS selector. For example,
                "After" means that the new HTML element should be inserted just after the target element, as a
                sibling. "Inside Top" means that the new HTML element should be inserted inside the target element
                as the first child.')
            )
            ->setVariable(ViewVariableName::NAME,  InputName::ELEMENT_LOCATION);
    }
}