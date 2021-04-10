<?php

namespace ADT\BaseForm;

use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Forms\Form;
use Nette\Forms\IControl;

/**
 * @property-read EntityForm $form
 * @method onBeforeInit($form)
 * @method onAfterInit($form)
 * @method onAfterMapToForm($form)
 * @method onAfterMapToEntity($form)
 * @method onAfterProcess($form)
 */
abstract class BaseForm extends Control
{
	/** @var string|null */
	public ?string $templateFilename = null;

	/** @var bool */
	public bool $isAjax = true;

	/** @var bool */
	public bool $emptyHiddenToggleControls = false;

	/** @var callable[] */
	public $onBeforeInit = [];

	/** @var callable[] */
	public $onAfterInit = [];

	/** @var callable[] */
	public $onAfterMapToForm = [];

	/** @var callable[] */
	public $onAfterMapToEntity = [];

	/** @var callable[] */
	public $onAfterProcess = [];

	protected $row;

	public function __construct()
	{
		$this->monitor(Presenter::class, function($presenter) {
			$form = $this->getForm();

			/** @link BaseForm::validateFormCallback() */
			/** @link BaseForm::processFormCallback() */
			foreach(['onValidate' => 'validateFormCallback', 'onSuccess' => 'processFormCallback'] as $event => $callback) {
				// first argument of array_unshift has to be an array
				if ($form->$event === null) {
					$form->$event = [];
				}

				// we want default events to be executed first
				array_unshift($form->$event, [$this, $callback]);
			}

			$this->onBeforeInit($form);

			if ($this->row) {
				$form->setEntity($this->row);
			}

			$this->init($form);

			$this->onAfterInit($form);

			if ($this->row) {
				$form->mapToForm();

				$this->onAfterMapToForm($form);
			}

			if ($form->isSubmitted()) {
				if (is_bool($form->isSubmitted())) {
					$form->setSubmittedBy(null);
				}
				elseif ($form->isSubmitted()->getValidationScope() !== null) {
					$form->onValidate = [];
				}
			}
		});
	}

	/**
	 * @param Form $form
	 */
	public function validateFormCallback($form)
	{
		if (!method_exists($this, 'validateForm')) {
			return;
		}

		if ($this->row) {
			$this->validateForm($form->getEntity());
		}
		else {
			$this->validateForm($form->getUnsafeValues(null));
		}
	}

	private function processFormCallback($form)
	{
		if ($form->isSubmitted()->getValidationScope() !== null) {
			return;
		}

		// empty hidden toggles
		if ($this->emptyHiddenToggleControls) {
			$toggles = $form->getToggles();
			foreach ($form->getGroups() as $_group) {
				$label = $_group->getOption('label');
				if (isset($toggles[$label]) && $toggles[$label] === false) {
					foreach ($_group->getControls() as $_control) {
						$_control->setValue(null);
					}
				}
			}
		}

		if ($this->row) {
			$this->getForm()->mapToEntity();

			$this->onAfterMapToEntity($form);
		}

		if (method_exists($this, 'processForm')) {
			if ($this->row) {
				$this->processForm($form->getEntity());
			}
			else {
				$this->processForm($form->getValues());
			}
		}

		if ($form->isValid()) {
			$this->onAfterProcess($form, $form->getValues());
		}
	}

	public function render()
	{
		$this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . 'form.latte');

		$customTemplatePath = (
		(!empty($this->templateFilename))
			? $this->templateFilename
			: str_replace('.php', '.latte', $this->getReflection()->getFileName())
		);

		if (file_exists($customTemplatePath)) {
			$this->template->customTemplatePath = $customTemplatePath;
		}

		if ($this->isAjax) {
			$this->getForm()->getElementPrototype()->class[] = 'ajax';
		}

		if ($this->presenter->isAjax()) {
			$this->redrawControl('formArea');
		}

		$this->template->render();
	}

	protected function createComponentForm()
	{
		return new Form();
	}

	protected function _()
	{
		return call_user_func_array([$this->getForm()->getTranslator(), 'translate'], func_get_args());
	}

	public function setRow($row): self
	{
		$this->row = $row;
		return $this;
	}

	public function setOnBeforeInit(callable $onBeforeInit): self
	{
		$this->onBeforeInit[] = $onBeforeInit;
		return $this;
	}

	public function setOnAfterInit(callable $onAfterInit): self
	{
		$this->onAfterInit[] = $onAfterInit;
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

	public function setOnAfterProcess(callable $onAfterProcess): self
	{
		$this->onAfterProcess[] = $onAfterProcess;
		return $this;
	}

	/** @deprecated Use setOnAfterProcess instead */
	public function setOnSuccess(callable $onSuccess): self
	{
		$this->setOnAfterProcess($onSuccess);
		return $this;
	}

	public function getForm(): Form
	{
		return $this['form'];
	}
}
