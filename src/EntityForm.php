<?php

namespace ADT\BaseForm;

use ADT\DoctrineForms\ToManyContainer;
use ADT\DoctrineForms\ToOneContainer;
use ADT\EmailStrictInput;
use ADT\Forms\Controls\PhoneNumberInput;
use Kdyby\Replicator\Container;
use Nette\Application\UI\Form;
use Vodacek\Forms\Controls\DateInput;
use \Closure;

trait EntityFormTrait
{
	/**
	 * Adds global error message.
	 * @param  string|object  $message
	 */
	public function addError($message, bool $translate = true): void
	{
		if ($translate && $this->getTranslator()) {
			$message = $this->getTranslator()->translate($message);
		}
		parent::addError($message, false);
	}
}

if (trait_exists(\ADT\DoctrineForms\EntityForm::class)) {
	/**
	 * @method Container addDynamic($name, $factory, $createDefault = 0, $forceDefault = FALSE)
	 * @method DateInput addDate($name, $label, $type = 'datetime-local')
	 * @method PhoneNumberInput addPhoneNumber($name, $label = null)
	 * @method EmailStrictInput addEmailStrict($name, $label = null, $errorMessage = 'Invalid email address.')
	 * @method CurrencyInput addCurrency($name, $label = null, $currency = null)
	 * @method ToOneContainer toOne(string $name, Closure $containerFactory, ?Closure $entityFactory = null, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null)
	 * @method ToManyContainer toMany(string $name, Closure $containerFactory, ?Closure $entityFactory = null, ?string $isFilledComponentName = null, ?string $isRequiredMessage = null)
	 */
	class EntityForm extends Form
	{
		use EntityFormTrait;
		use \ADT\DoctrineForms\EntityForm;
	}
} else {
	/**
	 * @method Container addDynamic($name, $factory, $createDefault = 0, $forceDefault = FALSE)
	 * @method DateInput addDate($name, $label, $type = 'datetime-local')
	 * @method PhoneNumberInput addPhoneNumber($name, $label = null)
	 * @method EmailStrictInput addEmailStrict($name, $label = null, $errorMessage = 'Invalid email address.')
	 * @method CurrencyInput addCurrency($name, $label = null, $currency = null)
	 */
	class EntityForm extends Form
	{
		use EntityFormTrait;
	}
}

