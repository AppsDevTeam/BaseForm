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
 * @method onAfterMapToForm($form)
 * @method onAfterMapToEntity($form)
 * @method onAfterProcess($form)
 */
abstract class BaseForm extends Control
{
	const RENDERER_BOOTSTRAP4 = 'bootstrap4';
	const RENDERER_BOOTSTRAP5 = 'bootstrap5';

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

	public static string $renderer = self::RENDERER_BOOTSTRAP5;

	protected $row;

	abstract protected function init(EntityForm $form);

	public function __construct(Container $dic)
	{
		$this->monitor(Presenter::class, function($presenter) use ($dic) {
			$form = $this->getForm()->setDic($dic);

			/** @link BaseForm::validateFormCallback() */
			/** @link BaseForm::processFormCallback() */
			/** @link BaseForm::sendErrorPayload() */
			/** @link BaseForm::bootstrap4() */
			foreach(['onValidate' => 'validateFormCallback', 'onSuccess' => 'processFormCallback', 'onError' => 'sendErrorPayload', 'onRender' => static::$renderer] as $event => $callback) {
				// first argument of array_unshift has to be an array
				if ($form->$event === null) {
					$form->$event = [];
				}

				// we want default events to be executed first
				array_unshift($form->$event, [$this, $callback]);
			}

			if ($this->row) {
				$form->setEntity($this->row);
			}

			$this->onBeforeInit($form);

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

	public function validateFormCallback(EntityForm $form)
	{
		if (!method_exists($this, 'validateForm')) {
			return;
		}

		if ($this->row) {
			$this->validateForm($form->getEntity());
		}
		else {
			$this->validateForm($form->values);
		}
	}

	public function processFormCallback(EntityForm $form)
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

		$this->onAfterProcess($form, $form->getValues());
	}

	public static function sendErrorPayload(Form $form)
	{
		if ($form->getPresenter()->isAjax()) {
			$renderer = $form->getRenderer();
			$presenter = $form->getPresenter();
			
			call_user_func([static::class, static::$renderer], $form);

			$renderer->wrappers['error']['container'] = null;
			$presenter->payload->snippets['snippet-' . $form->getElementPrototype()->getAttribute('id') . '-errors'] = $renderer->renderErrors();

			$renderer->wrappers['control']['errorcontainer'] = null;
			/** @var IControl $control */
			foreach ($form->getControls() as $control) {
				if ($control->getErrors()) {
					$presenter->payload->snippets['snippet-' . $control->getHtmlId() . '-errors'] = $renderer->renderErrors($control);
				}
			}

			$presenter->sendPayload();
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
		$renderer->wrappers['control']['.file'] = 'form-control-file';
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

	public static function bootstrap5(Form $form): void
	{
		static::bootstrap4($form);

		$renderer = $form->getRenderer();
		$renderer->wrappers['pair']['container'] = 'div class=mb-3';
		$renderer->wrappers['control']['.file'] = 'form-control';

		foreach ($form->getControls() as $control) {
			$control->getLabelPrototype()->addClass('form-label');
		}
	}
}
