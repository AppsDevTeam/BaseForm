BaseForm
========

- Bootstrap 4 renderer
- all forms are ajax by default - you can turn this off by setting `$this->isAjax = false`
- if you want use toggles in a form, just add `$form->addGroup('anyNameYouWant')` before the element(s) you want to toggle and then do `$form['showElement']->addCondition($form::FILLED)->toggle('anyNameYouWant')`. After the element(s), just use `$form->addGroup()`. Method `addGroup` just wrap elements to div with `id` attribute set to `anyNameYouWant`, it won't render any group caption. If you want to render a caption, do it manually in latte and then call `{include renderGroup form => $form, group => 'nameOfYourGroup'}` 
- if you render a form manually, you can use macro `formPair` for render both label and input including Bootstrap 4 wrapping div and error divs
- empty labels are not rendered
- you can also render an entire container by `{include renderContainer container => $form['container']}`