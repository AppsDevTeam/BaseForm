<?php

namespace ADT\BaseForm;

use ADT\DoctrineForms\ToManyContainer;
use ADT\Forms\Controls\PhoneNumberInput;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\DI\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Form;
use Nette\Forms\IControl;

/**
 * @property-read EntityForm $form
 * @method onBeforeInit($form)
 * @method onAfterInit($form)
 * @method onBeforeProcess($form)
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
	public $onBeforeProcess = [];

	/** @var callable[] */
	public $onAfterProcess = [];

	protected $row;

	abstract protected function init(EntityForm $form);

	public function __construct(Container $dic)
	{
		$this->monitor(Presenter::class, function($presenter) use ($dic) {
			$form = $this->getForm()->setDic($dic);

			/** @link BaseForm::validateFormCallback() */
			/** @link BaseForm::processFormCallback() */
			/** @link BaseForm::errorFormCallback() */
			/** @link BaseForm::bootstrap4() */
			foreach(['onValidate' => 'validateFormCallback', 'onSuccess' => 'processFormCallback', 'onError' => 'errorFormCallback', 'onRender' => 'bootstrap4'] as $event => $callback) {
				if (method_exists($this, $callback)) {
					if ($form->$event === null) {
						$form->$event = [];
					}

					// we want default events to be executed first
					array_unshift($form->$event, [$this, $callback]);
				}
			}

			if ($this->row) {
				$form->setEntity($this->row);
			}

			$this->onBeforeInit($form);

			$this->init($form);

			if ($this->row) {
				$form->mapToForm();
			}

			$this->onAfterInit($form);

			if ($form->isSubmitted()) {
				if (is_bool($form->isSubmitted())) {
					$form->setSubmittedBy(null);
				}
				elseif ($form->isSubmitted()->getValidationScope() !== null) {
					$form->onValidate = null;
				}
			}
		});
	}

	public function validateFormCallback(EntityForm $form)
	{
		if ($this->row) {
			$this->validateForm($form->getEntity());
		}
		else {
			$this->validateForm($form->values);
		}
	}

	public function processFormCallback(EntityForm $form)
	{
		$this->onBeforeProcess($form);

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
			$this->processForm($form->getEntity());
		}
		else {
			$this->processForm($form->values);
		}

		$this->onAfterProcess($form);
	}

	public function errorFormCallback(EntityForm $form)
	{
		if ($this->presenter->isAjax()) {
			$renderer = $form->getRenderer();
			$this->bootstrap4($form);

			$renderer->wrappers['error']['container'] = null;
			$this->presenter->payload->snippets['snippet-' . $form->getElementPrototype()->getAttribute('id') . '-errors'] = $renderer->renderErrors();

			$renderer->wrappers['control']['errorcontainer'] = null;
			/** @var IControl $control */
			foreach ($form->getControls() as $control) {
				if ($control->getErrors()) {
					$this->presenter->payload->snippets['snippet-' . $control->getHtmlId() . '-errors'] = $renderer->renderErrors($control);
				}
			}

			$this->presenter->sendPayload();
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
		return new EntityForm();
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

	public function setOnBeforeProcess(callable $onBeforeProcess): self
	{
		$this->onBeforeProcess[] = $onBeforeProcess;
		return $this;
	}

	public function setOnAfterProcess(callable $onAfterProcess): self
	{
		$this->onAfterProcess[] = $onAfterProcess;
		return $this;
	}

	public function setOnSuccess(callable $onSuccess): self
	{
		$this['form']->onSuccess[] = $onSuccess;
		return $this;
	}

	/**
	 * @return EntityForm
	 */
	public function getForm()
	{
		return $this['form'];
	}

	public static function bootstrap4(Form $form): void
	{
		$renderer = $form->getRenderer();
		$renderer->wrappers['error']['container'] = 'div';
		$renderer->wrappers['error']['item'] = 'div class="alert alert-danger"';
		$renderer->wrappers['controls']['container'] = null;
		$renderer->wrappers['group']['container'] = null;
		$renderer->wrappers['pair']['container'] = 'div class=form-group';
		$renderer->wrappers['label']['container'] = null;
		$renderer->wrappers['control']['container'] = null;
		$renderer->wrappers['control']['.error'] = 'is-invalid';
		$renderer->wrappers['control']['errorcontainer'] = 'div class=invalid-feedback';
		$renderer->wrappers['control']['erroritem'] = 'div';
		$renderer->wrappers['control']['description'] = 'small class=form-text text-muted';

		// we need to create a template container for ToManyContainer
		// to apply bootstrap4 styles below
		/** @var ToManyContainer $_toManyContainer */
		foreach ($form->getComponents(true, ToManyContainer::class) as $_toManyContainer) {
			if ($_toManyContainer->isAllowAdding()) {
				$_toManyContainer->getTemplate();
			}
		}

		/** @var BaseControl $control */
		foreach ($form->getControls() as $control) {
			$type = $control->getOption('type');
			if ($type === 'button') {
				if ($control->getValidationScope() !== null) {
					$control->getControlPrototype()->addClass('btn btn-outline-secondary');
				} else {
					$control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-outline-secondary');
					$usedPrimary = true;
				}

				if ($control->getControl()->attrs['value']) {
					$control->getControlPrototype()->setName('button')
						->addAttributes($control->getControl()->attrs)
						->addHtml('<span class="js-spinner spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>')
						->addHtml($control->getControl()->attrs['value']);
				}

			} elseif ($type === 'file') {
				$control->getControlPrototype()->addClass('form-control-file');

			} elseif (in_array($type, ['checkbox', 'radio'], true)) {
				if ($control instanceof Checkbox) {
					$control->getLabelPrototype()->addClass('form-check-label');
				} else {
					$control->getItemLabelPrototype()->addClass('form-check-label');
				}
				$control->getControlPrototype()->addClass('form-check-input');
				$control->getSeparatorPrototype()->setName('div')->addClass('form-check');

			} elseif ($control instanceof PhoneNumberInput) {
				$control->getControlPrototype(PhoneNumberInput::CONTROL_COUNTRY_CODE)->addClass('form-control');
				$control->getControlPrototype(PhoneNumberInput::CONTROL_NATIONAL_NUMBER)->addClass('form-control');

			} else {
				$control->getControlPrototype()->addClass('form-control');
			}
		}
	}
}
