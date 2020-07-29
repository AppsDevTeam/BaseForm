<?php

namespace ADT\BaseForm;

use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\Checkbox;

/**
 * @property-read EntityForm $form
 * @method onBeforeInit($form)
 * @method onAfterInit($form)
 */
abstract class BaseForm extends Control
{
	public $templateFilename = NULL;
	public $isAjax = true;

	/** @var callable[] */
	public $onBeforeInit = [];

	/** @var callable[] */
	public $onAfterInit = [];

	protected $row;

	abstract protected function init(EntityForm $form);

	public function __construct()
	{
		$this->monitor(Presenter::class, function($presenter) {
			/** can't be named "attached" because of Nette 2.4 compatibility */
			$this->attach($presenter);
		});

		// we have to register callbacks here to ensure execution in the right order
		// when another callback is added in the presenter
		$form = $this->getForm();

		if (method_exists($this, 'validateForm')) {
			/** @link BaseForm::validateFormCallback() */
			$form->onValidate[] = [$this, 'validateFormCallback'];
		}

		/** @link BaseForm::processFormCallback() */
		if (method_exists($this, 'processForm')) {
			$form->onSuccess[] = [$this, 'processFormCallback'];
		}

		/** @link BaseForm::errorFormCallback() */
		$form->onError[] = [$this, 'errorFormCallback'];
	}

	protected function attach(Presenter $presenter): void
	{
		$form = $this->getForm();

		$form->setRenderer(new FormRenderer($form));

		$form->setTranslator($presenter->translator);

		$this->onBeforeInit($form);

		$this->init($form);

		if ($this->row) {
			$this->bindEntity();
		}

		$this->onAfterInit($form);

		if ($form->isSubmitted()) {
			if (is_bool($form->isSubmitted())) {
				$form->setSubmittedBy(null);
			}
			elseif ($form->isSubmitted()->getValidationScope() === []) {
				$form->onValidate = null;
			}
		}
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
		if ($this->row) {
			$this->processForm($form->getEntity());
		}
		else {
			$this->processForm($form->values);
		}
	}

	public function errorFormCallback(EntityForm $form)
	{
		if ($this->presenter->isAjax()) {
			$renderer = $form->getRenderer();
			$this->bootstrap4($form);

			$renderer->wrappers['error']['container'] = null;
			$this->presenter->payload->snippets['snippet-' . $form->getElementPrototype()->getAttribute('id') . '-errors'] = $renderer->renderErrors();

			$renderer->wrappers['control']['errorcontainer'] = null;
			foreach ($form->getControls() as $control) {
				if ($control->getErrors()) {
					$this->presenter->payload->snippets['snippet-' . $control->getHtmlId() . '-errors'] = $renderer->doRenderErrors($control);
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

	public function setRow($row)
	{
		$this->row = $row;
		return $this;
	}

	/**
	 * @return EntityForm
	 */
	public function getForm()
	{
		return $this['form'];
	}

	protected function createComponentForm()
	{
		$form = new EntityForm();
		$form->onRender[] = [$this, 'bootstrap4'];
		return $form;
	}

	protected function bindEntity()
	{
		$this->getForm()->bindEntity($this->row);
	}

	protected function _()
	{
		return call_user_func_array($this->getForm()->getTranslator()->translate, func_get_args());
	}

	public function bootstrap4(EntityForm $form): void
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

		foreach ($form->getControls() as $control) {
			$type = $control->getOption('type');
			if ($type === 'button') {
				if ($control->getValidationScope() === []) {
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

			} else {
				$control->getControlPrototype()->addClass('form-control');
			}
		}
	}
}
