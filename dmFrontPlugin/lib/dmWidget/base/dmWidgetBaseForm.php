<?php

abstract class dmWidgetBaseForm extends dmForm
{
  protected
    $dmWidget,
    $firstDefaults = array(),
    $stylesheets = array(),
    $javascripts = array();

  /**
   * Constructor.
   *
   * @param dmWidget $widget    A widget
   * @param array  $options     An array of options
   * @param string $CSRFSecret  A CSRF secret (false to disable CSRF protection, null to use the global CSRF secret)
   */
  public function __construct($widget, $options = array(), $CSRFSecret = null)
  {
    if (!$widget instanceof DmWidget)
    {
      throw new dmException(sprintf('%s must be initialized with a DmWidget, not a %s', get_class($this), gettype($widget)));
    }

    $this->dmWidget = $widget;
    
    parent::__construct($widget->values, $options, $CSRFSecret);
  }
  
  public function setup()
  {
    parent::setup();
    
    $this->setName($this->name.'_'.$this->dmWidget->get('id'));
  }

  public function configure()
  {
    parent::configure();

    $this->widgetSchema['cssClass']     = new sfWidgetFormInputText;
    $this->validatorSchema['cssClass']  = new dmValidatorCssClasses(array('required' => false));
    
    $this->widgetSchema['cssClass']->setLabel('CSS class');

    $this->setDefault('cssClass', $this->dmWidget->cssClass);
  }

  protected function addRequiredJavascript($keys)
  {
    $this->javascripts = array_merge($this->javascripts, (array) $keys);
  }
  
  public function getJavascripts()
  {
    return $this->javascripts;
  }
  
  protected function addRequiredStylesheet($keys)
  {
    $this->stylesheets = array_merge($this->stylesheets, (array) $keys);
  }
  
  public function getStylesheets()
  {
    return $this->stylesheets;
  }
  
  /*
   * Overload this method to alter form values
   * when form has been validated
   */
  public function getWidgetValues()
  {
    $values = $this->getValues();

    unset($values['cssClass']);

    return $values;
  }

  public function render($attributes = array())
  {
    $attributes = dmString::toArray($attributes, true);

    return
    $this->open($attributes).
    '<ul class="dm_form_elements">'.$this->renderContent($attributes).'</ul>'.
    $this->renderActions().
    $this->close();
  }

  protected function renderContent($attributes)
  {
    return $this->getFormFieldSchema()->render($attributes);
  }

  protected function renderActions()
  {
    $i18n = dm::getI18n();
    
    return sprintf(
      '<div class="actions">
        <div class="actions_part clearfix">%s%s</div>
        <div class="actions_part clearfix">%s%s</div>
      </div>',
      sprintf('<a class="dm cancel close_dialog button fleft">%s</a>', $i18n->__('Cancel')),
      sprintf('<input type="submit" class="submit try blue fright" name="try" value="%s" />', $i18n->__('Try')),
      sprintf('<a class="dm delete button red fleft" title="%s">%s</a>', $i18n->__('Delete this widget'), $i18n->__('Delete')),
      sprintf('<input type="submit" class="submit and_save green fright" name="and_save" value="%s" />', $i18n->__('Save and close'))
    );
  }

  /*
   * Try to guess default values
   * from last updated widget with same module.action
   * @return array default values
   */
  protected function getDefaultsFromLastUpdated(array $fields = array())
  {
    if ($this->dmWidget->get('value'))
    {
      return array_merge($this->dmWidget->getValues(), array('cssClass' => $this->dmWidget->get('css_class')));
    }

    $lastWidgetValue = dmDb::query('DmWidget w')
    ->withI18n(null, null , 'w')
    ->where('w.module = ? AND w.action = ?', array($this->dmWidget->get('module'), $this->dmWidget->get('action')))
    ->orderBy('w.updated_at desc')
    ->limit(1)
    ->select('w.id, wTranslation.value as value')
    ->fetchOneArray();
    
    $defaults = $this->getFirstDefaults();

    if (!$lastWidgetValue)
    {
      return $defaults;
    }

    $values = json_decode((string) $lastWidgetValue['value'], true);

    foreach($fields as $field)
    {
      $defaults[$field] = dmArray::get($values, $field, dmArray::get($defaults, $field));
    }
    
    return $defaults;
  }

  protected function getFirstDefaults()
  {
    return $this->firstDefaults;
  }
  
}