<?php
namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;
use Exception;
use Application\Utility\ApplicationErrorLogger;
use Application\Event\ApplicationEvent;

class ApplicationSettingAdministration extends ApplicationSetting
{
    /**
     * Save settings
     *
     * @param array $settingsList
     * @param array $settingsValues
     * @param string $currentlanguage
     * @param string $module
     * @return boolean|string
     */
    public function saveSettings(array $settingsList, array $settingsValues, $currentlanguage, $module)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // save settings
            foreach ($settingsList as $setting) {
                if (array_key_exists($setting['name'], $settingsValues)) {
                    // remove previously value
                    $query = $this->delete('application_setting_value')
                        ->where([
                            'setting_id' => $setting['id']
                        ])
                        ->where((!$setting['language_sensitive'] ? 'language is null' : ['language' => $currentlanguage]));

                    $statement = $this->prepareStatementForSqlObject($query);
                    $statement->execute();

                    // insert new value
                    $extraValues = $setting['language_sensitive']
                        ? ['language' => $currentlanguage]
                        : [];

                    $value = is_array($settingsValues[$setting['name']])
                        ? implode(self::SETTINGS_ARRAY_DIVIDER, $settingsValues[$setting['name']])
                        : (null != $settingsValues[$setting['name']] ? $settingsValues[$setting['name']] : '');

                    $query = $this->insert('application_setting_value')
                        ->values(array_merge([
                           'setting_id' => $setting['id'],
                           'value' => $value
                        ], $extraValues));

                    $statement = $this->prepareStatementForSqlObject($query);
                    $statement->execute();
                }
            }

            // clear cache
            $this->removeSettingsCache($currentlanguage);
            self::$settings = null;

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the change settings event
        ApplicationEvent::fireChangeSettingsEvent($module);
        return true;
    }

    /**
     * Get settings list
     *
     * @param string $module
     * @param string $language
     * @return array|boolean
     */
    public function getSettingsList($module, $language)
    {
        // get module info
        if (null != ($moduleInfo = $this->getModuleInfo($module))) {
            $subQuery= $this->select();
            $subQuery->from(['c' => 'application_setting_value'])
                ->columns([
                    'id'
                ])
                ->limit(1)
                ->where(['a.id' => new Expression('c.setting_id')])
                ->where
                    ->and->equalTo('c.language', $language)
                ->where
                    ->or->equalTo('a.id', new Expression('c.setting_id'))
                    ->and->isNull('c.language');

            $mainSelect = $this->select();
            $mainSelect->from(['a' => 'application_setting'])
                ->columns([
                    'id',
                    'name',
                    'label',
                    'description',
                    'description_helper',
                    'type',
                    'required',
                    'language_sensitive',
                    'values_provider',
                    'check',
                    'check_message'
                ])
                ->join(
                    ['b' => 'application_setting_value'],
                    new Expression('b.id = (' .$this->getSqlStringForSqlObject($subQuery) . ')'),
                    [
                        'value'
                    ],
                    'left'
                )
                ->join(
                    ['d' => 'application_setting_category'],
                    new Expression('a.category = d.id'),
                    [
                        'category_name' => new Expression('d.name')
                    ],
                    'left'
                )
                ->order('a.order')
                ->where(['a.module' => $moduleInfo['id']])
                ->where
                    ->and->notEqualTo('a.type', self::SYS_SETTINGS_FLAG);

            $statement = $this->prepareStatementForSqlObject($mainSelect);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            // processing settings list
            $settings = [];
            foreach ($resultSet as $setting) {
                // convert an array
                $settingValue = $this->convertString($setting->type, $setting->value);

                $settings[$setting->id] = [
                    'id' => $setting->id,
                    'category' => $setting->category_name,
                    'name' => $setting->name,
                    'label' => $setting->label,
                    'description' => $setting->description_helper
                        ? eval($setting->description_helper)
                        : $setting->description,
                    'type' => $setting->type,
                    'required' => $setting->required,
                    'language_sensitive'  => $setting->language_sensitive,
                    'value' => $settingValue,
                    'values_provider' => $setting->values_provider,
                    'max_length' => self::SETTING_VALUE_MAX_LENGTH 
                ];

                // add extra validators
                if ($setting->check) {
                    $settings[$setting->id]['validators'][] = [
                        'name' => 'callback',
                        'options' => [
                            'message' => $setting->check_message,
                            'callback' => function($value) use ($setting) {
                                return eval(str_replace('__value__', $value, $setting->check));
                            }
                        ]
                    ];
                }
            }

            // get list of predefined values
            $select = $this->select();
            $select->from('application_setting_predefined_value')
                ->columns([
                    'setting_id',
                    'value'
                ])
                ->where->in('setting_id', array_keys($settings));

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            // processing predefined list of values
            foreach ($resultSet as $values) {
                $settings[$values->setting_id]['values'][$values->value] = $values->value;
            }

            return $settings;
        }

        return false;
    }
}