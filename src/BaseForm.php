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

	public function __construct(\Nette\DI\Container $dic)
	{
		$this->injectProperties($dic);
		parent::__construct();
		return $this;
	}

	public function createComponentForm()
	{
		return new Form();
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
		$this->init($form);
		
		$form->setDefaults($this->defaults);
		
		$this->getForm()->addHidden('_invalidate');

		if (method_exists($this, 'validateForm')) {
			$form->onValidate[] = $this->validateFormCallback;
		}

		if ($this->ajax) {
			$form->getElementPrototype()->class[] = 'adt-form';
		}
		
		$this->templateFilename = ($this->templateFilename ? $this->templateFilename : str_replace('.php' ,'.latte', $this->reflection->getFileName()));
		
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
			$form->onSuccess[] = $this->processFormCallback;
			if($this->ajax) {
				$form->onError[] = function() {
					$this->redrawControl();
				};
			}
		}
	}

	public function processFormCallback(Form $form)
	{
		$this->processForm($form->values);
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
		$this->template->renderer = $this->form->renderer;
		$this->template->render();
	}
}