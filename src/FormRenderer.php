<?php

namespace ADT\BaseForm;

use ADT\Forms\Controls\PhoneNumberInput;
use Nette;
use Nette\Forms\Rendering\DefaultFormRenderer;
use Nette\Utils\Html;

class FormRenderer extends DefaultFormRenderer
{
	public function renderLabel(Nette\Forms\IControl $control): Html
	{
		if ($control->getLabel() && $control->getLabel()->getHtml()) {
			return parent::renderLabel($control);
		}

		return Html::el();
	}

	public function renderControl(Nette\Forms\IControl $control): Html
	{
		$el = parent::renderControl($control);

		// Is this an instance of a RadioList or CheckboxList?
		if (
			$control instanceof Nette\Forms\Controls\RadioList ||
			$control instanceof Nette\Forms\Controls\CheckboxList
		) {
			// Get original separator
			$sep = $control->getSeparatorPrototype();
			$sep->setHtml('');

			// Create an empty Html container object
			$el = Html::el();

			// Get all the child items
			$items = $control->getItems();
			// For each child item, add the appropriate control part and label part after one another
			foreach($items as $key => $item) {
				$_sep = clone $sep;
				$_sep->addHtml($control->getControlPart($key));
				$_sep->addHtml($control->getLabelPart($key));
				$el->addHtml($_sep);
			}
		}
		elseif ($control instanceof Nette\Forms\Controls\Checkbox) {
			// Create an empty Html container object
			$el = Html::el();

			// Get original separator
			$sep = $control->getSeparatorPrototype();
			$sep->setHtml('');

			$_sep = clone $sep;
			$_sep->addHtml($control->getControlPart());
			$_sep->addHtml($control->getLabelPart());
			$el->addHtml($_sep);
		}
		elseif ($control instanceof PhoneNumberInput) {
			$el = Html::el('div')
				->setAttribute('class', 'form-row')
				->addHtml('<div class="col-4">' . $control->getControlPart(PhoneNumberInput::CONTROL_COUNTRY_CODE)->addClass('form-control') . '</div>')
				->addHtml('<div class="col-8">' . $control->getControlPart(PhoneNumberInput::CONTROL_NATIONAL_NUMBER)->addClass('form-control') . "</div>");
		}

		return $el;
	}
}
