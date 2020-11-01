<?php

namespace ADT\BaseForm;

use ADT\EmailStrictInput;
use ADT\Forms\Controls\CurrencyInput;
use ADT\Forms\Controls\PhoneNumberInput;
use Kdyby\Replicator\Container;
use Nette\Application\UI\Form;
use Nette\Forms\IFormRenderer;
use Vodacek\Forms\Controls\DateInput;

/**
 * @method Container addDynamic($name, $factory, $createDefault = 0, $forceDefault = FALSE)
 * @method DateInput addDate($name, $label, $type = 'datetime-local')
 * @method PhoneNumberInput addPhoneNumber($name, $label = null)
 * @method EmailStrictInput addEmailStrict($name, $label = null, $errorMessage = 'Invalid email address.')
 * @method CurrencyInput addCurrency($name, $label = null, $currency = null)
 */
class EntityForm extends Form
{
	use \Kdyby\DoctrineForms\EntityForm;

	protected ?IFormRenderer $renderer = null;

	/**
	 * Returns form renderer.
	 */
	public function getRenderer(): IFormRenderer
	{
		if ($this->renderer === null) {
			$this->renderer = new FormRenderer($this);
		}
		return $this->renderer;
	}
}
