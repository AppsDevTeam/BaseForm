<?php

namespace ADT\BaseForm;

use ADT\Forms\Controls\PhoneNumberInput;
use Nette;
use Nette\Forms\Rendering\DefaultFormRenderer;
use Nette\Utils\Html;
use Nette\Utils\IHtmlString;

class FormRenderer extends DefaultFormRenderer
{
	public function __construct(Nette\Forms\Form $form)
	{
		$this->form = $form;
	}

	public function renderLabel(Nette\Forms\IControl $control): Html
	{
		if ($control->getLabel() && $control->getLabel()->getHtml()) {
			return parent::renderLabel($control);
		}

		return Html::el();
	}

	public function renderControl(Nette\Forms\IControl $control): Html
	{
		$body = $this->getWrapper('control container');
		if ($this->counter % 2) {
			$body->class($this->getValue('control .odd'), true);
		}
		if (!$this->getWrapper('pair container')->getName()) {
			$body->class($control->getOption('class'), true);
			$body->id = $control->getOption('id');
		}

		$description = $control->getOption('description');
		if ($description instanceof IHtmlString) {
			$description = ' ' . $description;

		} elseif ($description != null) { // intentionally ==
			if ($control instanceof Nette\Forms\Controls\BaseControl) {
				$description = $control->translate($description);
			}
			$description = ' ' . $this->getWrapper('control description')->setText($description);

		} else {
			$description = '';
		}

		if ($control->isRequired()) {
			$description = $this->getValue('control requiredsuffix') . $description;
		}

		$control->setOption('rendered', true);
		$el = $control->getControl();
		if ($el instanceof Html) {
			if ($el->getName() === 'input') {
				$el->class($this->getValue("control .$el->type"), true);
			}
			$el->class($this->getValue('control .error'), $control->hasErrors());
		}

		$el = $body->setHtml($el . $description . $this->doRenderErrors($control));

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
				->addHtml('<div class="col-8">' . $control->getControlPart(PhoneNumberInput::CONTROL_NATIONAL_NUMBER)->addClass('form-control') . $description . $this->doRenderErrors($control) . '</div>');
		}

		return $el;
	}

	/**
	 * Renders validation errors (per form or per control).
	 */
	public function renderErrors(Nette\Forms\IControl $control = null, bool $own = true): string
	{
		$errors = $control
			? $control->getErrors()
			: ($own ? $this->form->getOwnErrors() : $this->form->getErrors());
		return $this->doRenderErrors($control, $errors);
	}

	public function doRenderErrors(Nette\Forms\IControl $control = null, $errors = []): string
	{
		$container = $this->getWrapper($control ? 'control errorcontainer' : 'error container');
		$item = $this->getWrapper($control ? 'control erroritem' : 'error item');

		$elId = ($control ? $control->getHtmlId(): $this->form->getElementPrototype()->getAttribute('id'));

		$container->setAttribute('id', 'snippet-' . $elId . '-errors');

		if ($control) {
			$errors = $control->getErrors();
		}

		foreach ($errors as $error) {
			$item = clone $item;
			if ($error instanceof IHtmlString) {
				$item->addHtml($error);
			} else {
				$item->setText($error);
			}
			$container->addHtml($item);
		}

		if ($control && $errors) {
			$container->addHtml('<script>document.getElementById("' . $elId . '").classList.add("is-invalid");</script>');
		}

		return "\n" . $container->render($control ? 1 : 0);
	}
}
