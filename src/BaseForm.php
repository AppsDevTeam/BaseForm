<?php

namespace ADT;

use \Nette\Application\UI\Form;

/**
 * @property-read \Nette\Application\UI\Form $form
 * @property-read \App\Presenters\BasePresenter $presenter
 */
abstract class BaseForm extends \Nette\Application\UI\Control
{
	public $templateFilename = NULL;

	protected $ajax = FALSE;

	protected $defaults = array();

	abstract function init(Form $form);

	abstract function processForm($values);

	/**
	 * Pokud chceš nastavit method nebo cokoliv jiného, přičemž ještě nesmí existovat
	 * žádné prvky, poděd tuto funkci, přes parent::createComponentForm() získej form
	 * a nastav mu vše co potřebuješ.
	 *
	 * @return Form
	 */
	public function createComponentForm()
	{
		return new BaseUIForm();
	}

	/**
	 * @return \Nette\Application\UI\Form
	 */
	public function getForm()
	{
		return $this['form'];
	}

	public function setDefaults($defaults)
	{
		$this->defaults = $defaults;
	}

	protected function attached($presenter)
	{
		parent::attached($presenter);

		$form = $this->getForm();

		$form->addHidden('_invalidate');

		if(isset($presenter->translator)) {
			$form->setTranslator($presenter->translator);
		}

		$this->init($form);

		$form->setDefaults($this->defaults);

		if (method_exists($this, 'validateForm')) {
			$form->onValidate[] = $this->validateFormCallback;
		}

		if ($this->ajax) {
			$form->getElementPrototype()->class[] = 'adt-form';
		}

		$this->templateFilename = ($this->templateFilename ? $this->templateFilename : str_replace('.php' ,'.latte', $this->getReflection()->getFileName()));

		if ($this->presenter->isAjax() && $form['_invalidate']->value) {
			$this->redrawControl('formArea');
			if (file_exists($this->templateFilename)) {
				foreach(explode(' ', $form['_invalidate']->value) as $snippetName) {
					$this->redrawControl($snippetName);
				}
			} else {
				$this->redrawControl('controls');
				foreach(explode(' ', $form['_invalidate']->value) as $snippetName) {
					$this->redrawControl('controls-' . $snippetName);
				}
			}
		}	else {

			if ($form->onSuccess === NULL) {
				$form->onSuccess = [];
			}
			array_unshift($form->onSuccess, [$this, 'processFormCallback']);

			if($this->ajax) {
				$form->onError[] = function() {
					$this->redrawControl();
				};
			}
		}

		$this->template->renderer = $this->form->renderer;
	}

	public function processFormCallback(Form $form)
	{
		$values = $form->values;
		foreach ($values as $key => $value) {
			if ($form[$key] instanceof \Nette\Forms\Controls\Checkbox && $value === FALSE) {
				$values[$key] = 0;
			} elseif ($value === '' || $value === ':null') {
				$values[$key] = NULL;
			}
		}

		$this->processForm($values);
	}

	public function validateFormCallback(Form $form)
	{
		$this->validateForm($form->values);
	}

	public function render()
	{
		if (!file_exists($this->templateFilename)) {
			$this->templateFilename = __DIR__ . DIRECTORY_SEPARATOR . '@baseForm.latte';
		}

		$this->template->setFile($this->templateFilename);
		$this->template->render();
	}
}
