<?php

namespace ADT\BaseForm;

use Nette\Application\UI\Form;

/**
 * @method addDynamic($name, $factory, $createDefault = 0, $forceDefault = FALSE)
 * @method addDate($name, $label, $type = 'datetime-local')
 * @method addPhoneNumber($name, $label = null)
 * @method addEmailStrict($name, $label = null, $errorMessage = 'Invalid email address.')
 */
class EntityForm extends Form
{
	use \Kdyby\DoctrineForms\EntityForm;
}
