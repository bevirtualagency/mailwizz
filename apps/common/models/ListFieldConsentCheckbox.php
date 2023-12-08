<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListFieldConsentCheckbox
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.5
 */

/**
 * Class ListFieldConsentCheckbox
 */
class ListFieldConsentCheckbox extends ListField
{
    /**
     * @var int
     */
    public $currentIndex = 0;

    /**
     * @var string
     */
    public $consent_text = 'I give my consent to [COMPANY_NAME] to send me occasional newsletters using the information i have provided in this form.';

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['consent_text', 'required'],
            ['consent_text', 'length', 'min' => 1, 'max' => 255],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'consent_text' => t('list_fields', 'The consent text'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListField the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListField $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'consent_text' => t('list_fields', 'The consent text shown to the subscriber.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        $this->required = self::TEXT_YES;
        parent::afterConstruct();
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->modelMetaData->getModelMetaData()->add('consent_text', (string)$this->consent_text);
        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->consent_text = (string)$this->modelMetaData->getModelMetaData()->itemAt('consent_text');
        parent::afterFind();
    }
}
