<?php

namespace Adt\BaseForm;

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

	protected function mapToEntity()
	{
		if (property_exists($this->entity, 'rawValue')) {
			$this->entity->rawValue = true;
		}

		$this->getEntityMapper()->save($this->entity, $this);

		if (property_exists($this->entity, 'rawValue')) {
			$this->entity->rawValue = false;
		}
	}
}
