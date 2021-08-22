<?php

namespace ADT\DoctrineForms;

/**
 * @property-read Form $form
 * @method onAfterMapToForm($form)
 * @method onAfterMapToEntity($form)
 */
abstract class BaseForm extends \ADT\Forms\BaseForm
{
	protected $entity;

	/**
	 * @internal
	 * @var callable[]
	 */
	public $onAfterMapToForm = [];

	/**
	 * @internal
	 * @var callable[]
	 */
	public $onAfterMapToEntity = [];

	public function __construct()
	{
		parent::__construct();

		$this->setOnBeforeInitForm(function($form) {
			if ($this->entity) {
				$form->setEntity($this->entity);
			}
		});

		// we don't call setter intentionally to avoid logic in setter
		$this->onAfterInitForm[] = [$this, 'initOnAfterMapToForm'];

		$this->setOnBeforeProcessForm(function($form) {
			if ($this->getForm()->getEntity()) {
				$this->getForm()->mapToEntity();

				$this->onAfterMapToEntity($form);
			}
		});

		$this->paramResolvers[] = function($type) {
			if (is_subclass_of($type, Entity::class)) {
				return $this->entity;
			}

			return false;
		};


	}

	// we need to call initOnAfterMapToForm last
	// so we will remove initOnAfterMapToForm, add callbak and add initOnAfterMapToForm again
	public function setOnAfterInitForm(callable $onAfterInitForm): self
	{
		array_pop($this->onAfterInitForm);
		$this->onAfterInitForm[] = $onAfterInitForm;
		$this->onAfterInitForm[] = [$this, 'initOnAfterMapToForm'];
		return $this;
	}

	public function setOnAfterMapToForm(callable $onAfterMapToForm): self
	{
		$this->onAfterMapToForm[] = $onAfterMapToForm;
		return $this;
	}

	public function setOnAfterMapToEntity(callable $onAfterMapToEntity): self
	{
		$this->onAfterMapToEntity[] = $onAfterMapToEntity;
		return $this;
	}

	final public function setEntity(Entity $entity): self
	{
		$this->entity = $entity;
		if (isset($this->components['form'])) {
			$this->getForm()->setEntity($entity);
		}
		
		if (method_exists($this, 'initEntity') && !$entity->getId()) {
			call_user_func([$this, 'initEntity'], $entity);
		}
		
		return $this;
	}

	protected function createComponentForm()
	{
		return new Form();
	}

	/**
	 * @return Form
	 */
	public function getForm()
	{
		return $this['form'];
	}

	/**
	 * @internal
	 */
	public function initOnAfterMapToForm(Form $form)
	{
		if ($this->getForm()->getEntity()) {
			$form->mapToForm();

			$this->onAfterMapToForm($form);
		}
	}
}
