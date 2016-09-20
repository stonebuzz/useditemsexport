<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginUseditemsexportConfig extends CommonDBTM {

   static $rightname = 'config';

   /**
    * Display name of itemtype
    *
    * @return value name of this itemtype
    **/
   static function getTypeName($nb=0) {

      return __('General setup of useditemsexport', 'useditemsexport');
   }

   /**
    * Print the config form
    *
    * @param $ID        Integer : ID of the item
    * @param $options   array
    *
    * @return Nothing (display)
   **/
   function showForm($ID, $options=array()) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Active')."</td>";
      echo "<td>";
      Dropdown::showYesNo("is_active",$this->fields["is_active"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Footer text', 'useditemsexport') . "</td>";
      echo "<td><input type='text' name='footer_text' size='60' value='" 
                  . $this->fields["footer_text"] . "'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Orientation', 'useditemsexport') . "</td>";
      echo "<td>";
         self::dropdownOrientation($this->fields["orientation"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Format', 'useditemsexport') . "</td>";
      echo "<td>";
         self::dropdownFormat($this->fields["format"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Language', 'useditemsexport') . "</td>";
      echo "<td>";
         self::dropdownLanguage($this->fields["language"]);
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
   }

   /**
    * Show dropdown Orientation (Landscape / Portrait)
    * @param value (current preselected value)
    * @return nothing (display dropdown)
    */
   function dropdownOrientation($value) {
      Dropdown::showFromArray("orientation",
                        array('L' => __('Landscape', 'useditemsexport'),
                              'P' => __('Portrait', 'useditemsexport')),
                        array('value'  => $value));
   }

   /**
    * Show dropdown Format (A4, A3, etc...)
    * @param value (current preselected value)
    * @return nothing (display dropdown)
    */
   function dropdownFormat($value) {
      Dropdown::showFromArray("format",
                        array('A3' => __('A3'),
                              'A4' => __('A4'),
                              'A5' => __('A5')),
                        array('value'  => $value));
   }

   /**
    * Show dropdown Language (fr, en, it, etc...)
    * @param value (current preselected value)
    * @return nothing (display dropdown)
    */
   function dropdownLanguage($value) {
      global $CFG_GLPI;

      $supported_languages = array('ca','cs','da','de','en','es','fr','it','nl','pt','tr');

      $languages = array();
      foreach ($CFG_GLPI['languages'] as $lang => $datas) {
         $short_code = substr($lang,0,2);
         if (in_array($short_code, $supported_languages)) {
            $languages[$short_code] = $datas[0];
         }
      }

      Dropdown::showFromArray("language", $languages,
                        array('value'  => $value));
   }

   /**
    * Load configuration plugin in GLPi Session
    *
    * @return nothing
    */
   static function loadInSession() {
      $config = new self();
      $config->getFromDB(1);
      unset($config->fields['id']);
      $_SESSION['plugins']['useditemsexport']['config'] = $config->fields;
   }

   /**
    * Install all necessary table for the plugin
    *
    * @return boolean True if success
    */
   static function install(Migration $migration) {
      global $DB;

      $table = getTableForItemType(__CLASS__);

      if (!TableExists($table)) {
         $migration->displayMessage("Installing $table");

         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int(11) NOT NULL AUTO_INCREMENT,
                     `footer_text` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT '',
                     `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                     `orientation` VARCHAR(1) NOT NULL DEFAULT 'P',
                     `format` VARCHAR(2) NOT NULL DEFAULT 'A4',
                     `language` VARCHAR(2) NOT NULL DEFAULT 'fr',
               PRIMARY KEY  (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
            $DB->query($query) or die ($DB->error());

         $query = "INSERT INTO `$table` (id) VALUES (1)";
         $DB->query($query) or die ($DB->error());
      }
   }

   /**
    * Uninstall previously installed table of the plugin
    *
    * @return boolean True if success
    */
   static function uninstall() {
      global $DB;

      $table = getTableForItemType(__CLASS__);

      $query = "DROP TABLE IF EXISTS  `".$table."`";
      $DB->query($query) or die ($DB->error());
   }

}