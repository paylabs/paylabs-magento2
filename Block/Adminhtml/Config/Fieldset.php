<?php

namespace Paylabs\Payment\Block\Adminhtml\Config;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Model\Config;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\Js;

/**
 * Class Fieldset
 *
 * Custom configuration fieldset for the Paylabs Payment module in the Magento admin panel.
 *
 * This class extends Magento's core functionality to provide custom behavior for the configuration
 * fieldset, including custom CSS class handling, header title, comments, and JavaScript functionality
 * to toggle the configuration sections.
 *
 * @package Paylabs\Payment\Block\Adminhtml\Config
 */
class Fieldset extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var Config
     * Backend configuration model for retrieving configuration data.
     */
    protected $_backendConfig;

    /**
     * Constructor for Fieldset class.
     *
     * Initializes necessary dependencies for the fieldset block, such as backend config model,
     * context, session, and JS helper.
     *
     * @param Context $context Magento backend context, providing access to session, request, etc.
     * @param Session $authSession The authentication session for the admin user.
     * @param Js $jsHelper Helper class for generating JavaScript snippets in the admin.
     * @param Config $backendConfig The backend configuration model for retrieving and saving configuration.
     * @param array $data Optional additional data for initializing the block.
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        Config $backendConfig,
        array $data = []
    ) {
        $this->_backendConfig = $backendConfig;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * Add a custom CSS class to the frontend configuration element.
     *
     * This method extends the default behavior to append 'with-button enabled' to the CSS class.
     *
     * @param AbstractElement $element The form element for which to add the CSS class.
     * @return string The final CSS class string.
     */
    protected function _getFrontendClass($element)
    {
        return parent::_getFrontendClass($element) . ' with-button enabled';
    }

    /**
     * Return custom HTML for the fieldset's header title.
     *
     * This method generates custom HTML for the header of the configuration fieldset,
     * including a button to toggle the collapse state of the section.
     *
     * @param AbstractElement $element The form element to which the header belongs.
     * @return string The HTML for the header title.
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getHeaderTitleHtml($element)
    {
        $html = '<div class="config-heading" >';
        $htmlId = $element->getHtmlId();
        $html .= '<div class="button-container"><button type="button"' .
            ' class="button action-configure' .
            '" id="' .
            $htmlId .
            '-head" onclick="toggleSolution.call(this, \'' .
            $htmlId .
            "', '" .
            $this->getUrl(
                'adminhtml/*/state'
            ) . '\'); return false;"><span class="state-closed">' . __(
                'Configure'
            ) . '</span><span class="state-opened">' . __(
                'Close'
            ) . '</span></button>';

        $html .= '</div>';
        $html .= '<div class="heading"><strong>' . $element->getLegend() . '</strong>';

        if ($element->getComment()) {
            $html .= '<span class="heading-intro">' . $element->getComment() . '</span>';
        }
        $html .= '<div class="config-alt"></div>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Return custom HTML for the fieldset's header comment.
     *
     * This method is used to return additional comments or information for the header.
     * In this case, it returns an empty string as no comments are added.
     *
     * @param AbstractElement $element The form element to which the comment belongs.
     * @return string The HTML for the header comment.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getHeaderCommentHtml($element)
    {
        return '';
    }

    /**
     * Get the collapsed state on load.
     *
     * This method controls whether the fieldset section is collapsed by default.
     *
     * @param AbstractElement $element The form element.
     * @return false The fieldset is not collapsed by default.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _isCollapseState($element)
    {
        return false;
    }

    /**
     * Get the extra JavaScript needed for handling fieldset collapse toggle.
     *
     * This method generates a custom JavaScript snippet to handle toggling the collapse
     * and scroll behavior for the configuration fieldset.
     *
     * @param AbstractElement $element The form element.
     * @return string The JavaScript code as a string.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getExtraJs($element)
    {
        $script = "require(['jquery', 'prototype'], function(jQuery){
            window.toggleSolution = function (id, url) {
                var doScroll = false;
                Fieldset.toggleCollapse(id, url);
                if ($(this).hasClassName(\"open\")) {
                    $$(\".with-button button.button\").each(function(anotherButton) {
                        if (anotherButton != this && $(anotherButton).hasClassName(\"open\")) {
                            $(anotherButton).click();
                            doScroll = true;
                        }
                    }.bind(this));
                }
                if (doScroll) {
                    var pos = Element.cumulativeOffset($(this));
                    window.scrollTo(pos[0], pos[1] - 45);
                }
            }
        });";

        return $this->_jsHelper->getScript($script);
    }
}
