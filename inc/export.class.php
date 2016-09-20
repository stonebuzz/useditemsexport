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

require ('../vendor/autoload.php');

class PluginUseditemsexportExport extends CommonDBTM {

   public static $rightname = 'plugin_useditemsexport_export';

   static function getTypeName($nb = 0) {

      return __('Used items export', 'useditemsexport');
   }

   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getType()=='User') {
         if ($_SESSION['glpishow_count_on_tabs']) {
            return self::createTabEntry(self::getTypeName(), self::countForItem($item));
         }
         return self::getTypeName();
      }
      return '';
   }

   /**
    * @see CommonGLPI::displayTabContentForItem()
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      if ($item->getType()=='User') {
         if (Session::haveRightsOr('plugin_useditemsexport_export', array(READ, CREATE, PURGE))) {
            
            $PluginUseditemsexportExport = new self();
            $PluginUseditemsexportExport->showForUser($item);

         } else {
            echo "<div align='center'><br><br><img src=\"" . $CFG_GLPI["root_doc"] .
                     "/pics/warning.png\" alt=\"warning\"><br><br>";
            echo "<b>" . __("Access denied") . "</b></div>";
         }

      }
   }

   /**
    * @param $item    CommonDBTM object
   **/
   public static function countForItem(CommonDBTM $item) {
      return countElementsInTable(getTableForItemType(__CLASS__), 
                                    "`users_id` = '".$item->getID()."'");
   }

   /**
    * Get all generated export for user.
    *
    * @param $users_id user ID
    *
    * @return array of exports
   **/
   static function getAllForUser($users_id) {
      global $DB;

      $exports = array();

      // Get default one
      foreach ($DB->request(getTableForItemType(__CLASS__), "`users_id` = '$users_id'") as $data) {
         $exports[$data['id']] = $data;
      }

      return $exports;
   }

   /**
    * @param CommonDBTM $item
    * @param array $options
    * @return nothing
    */
   function showForUser($item, $options = array()) {
      global $DB, $CFG_GLPI;

      $users_id = $item->getField('id');

      $exports = self::getAllForUser($users_id);

      $canpurge = self::canPurge();
      $cancreate = self::canCreate();

      if ($cancreate) {
         $rand = mt_rand();

         echo "<div class='center'>";
         echo "<form method='post' name='useditemsexport_form$rand' id='useditemsexport_form$rand'
                  action=\"" . $CFG_GLPI["root_doc"] . "/plugins/useditemsexport/front/export.form.php\">";

         echo "<table class='tab_cadre_fixehov'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>".__('Generate new export', 'useditemsexport');
               echo "&nbsp;&nbsp<input type='submit' name='generate' value=\""._sx('button', 'Create')."\" class='submit'>";
               echo "<input type='hidden' name='users_id' value='$users_id'>";
            echo "</th></tr>";
         echo "</table>";

         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='center'>";

      if ($canpurge) {
         $rand = mt_rand();

         echo "<form method='post' name='useditemsexport_form$rand' id='useditemsexport_form$rand'
                  action=\"" . $CFG_GLPI["root_doc"] . "/plugins/useditemsexport/front/export.form.php\">";
      }

      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='" . ($canpurge ? 5 : 4) . "'>" 
                     . __('Used items export generated', 'useditemsexport') . "</th></tr><tr>";

      if (count($exports) == 0) {

         echo "<tr class='tab_bg_1'>";
            echo "<td class='center' colspan='" . ($canpurge ? 5 : 4) . "'>"
                     .__('No item to display')."</td>";
         echo "</tr>";

      } else {
         if ($canpurge) {
            echo "<th>&nbsp;</th>";
         }
         echo "<th>" . __('Reference number of export', 'useditemsexport') . "</th>";
         echo "<th>" . __('Date of export', 'useditemsexport') . "</th>";
         echo "<th>" . __('Author of export', 'useditemsexport') . "</th>";
         echo "<th>" . __('Export document', 'useditemsexport') . "</th>";
         echo "</tr>";

         foreach ($exports as $data) {
            echo "<tr class='tab_bg_1'>";

               if ($canpurge) {
                  echo "<td width='10'>";

                  $sel = (isset($_GET["select"]) && ("all" == $_GET["select"]))
                        ? "checked"
                        : "";

                  echo "<input type='checkbox' name='useditemsexport[" . $data["id"] . "]' value='1' " . $sel . ">";
                  echo "</td>";
               }

               echo "<td class='center'>";
               echo isset($data["refnumber"]) ? $data["refnumber"] : "-";
               echo "</td>";

               echo "<td class='center'>";
               echo isset($data["date_mod"]) ? Html::convDateTime($data["date_mod"]) : "-";
               echo "</td>";

               $User = new User();
               $User->getFromDB($data['authors_id']);
               echo "<td class='center'>";
               echo isset($data["authors_id"]) ? $User->getLink() : "-";
               echo "</td>";

               $Doc = new Document();
               $Doc->getFromDB($data['documents_id']);
               echo "<td class='center'>";
               echo isset($data["documents_id"]) ? $Doc->getDownloadLink() : "-";
               echo "</td>";
            echo "</tr>";
         }

      }

      if ($canpurge && count($exports) > 0)   {

            echo "</table>";
            Html::openArrowMassives("useditemsexport_form$rand", true);
            Html::closeArrowMassives(array('purgeitem' => __('Purge')));
            Html::closeForm();
            echo "</div>";

      } else {

         echo "</table></div>";
      }

   }


   /**
    * Generate PDF for user and add entry into DB
    *
    * @param $users_id user ID
    *
    * @return array of exports
   **/
   static function generatePDF($users_id) {

      $num       = self::getNextNum();
      $refnumber = self::getNextRefnumber();

      ob_start();
      ?>
      <style type="text/css">
         table { width: 100%; font-size: 10pt; font-family: helvetica, arial, sans-serif }
      </style>
      <page backtop="70mm" backleft="10mm" backright="10mm" backbottom="30mm">
         <page_header>
            <table>
               <tr>
                  <td style="height: 60mm; width: 40%; text-align: center"><img src="../pics/logo.png" style="width:34mm; height:48mm"></td>
                  <td style="width: 60%; text-align: center;">
                     <strong style="font-size: 12pt">SOCIETE</strong><br />
                     SERVICE<br /><br /><br />
                     ADRESS<br />
                     CODEPOSTAL VILLE
                     <br /><br /><br />
                     Tel : 00.00.00.00.00 - Fax : 00.00.00.00.00
                  </td>
               </tr>
            </table>
         </page_header>

         <table>
            <tr>
               <td style="border: 1px solid #000000; text-align: center; width: 100%; font-size: 15pt; height: 8mm;">
                  ASSET EXPORT N° <?php echo $refnumber; ?>
               </td>
            </tr>
         </table>

         <br><br><br><br><br>
         <table>
            <tr>
               <th style="width: 20%;">
                  SERIAL NUMBER : 
               </th>
               <th style="width: 20%;">
                  NAME : 
               </th>
               <th style="width: 20%;">
                  TYPE :
               </th>
            </tr>
            <?php
/*
            $allUsedItemsForUser = self::getAllUsedItemsForUser($users_id);

            foreach ($allUsedItemsForUser as $itemtype) {

               $item = new $itemtype();

               foreach ($itemtype as $item) {
            
            ?>
            <tr>
               <td style="width: 80%;">
                  <?php echo $item['serial']; ?>
               </td>
               <td style="width: 80%;">
                  <?php echo $item['name']; ?>
               </td>
               <td style="width: 80%;">
                  <?php echo $itemtype; ?>
               </td>
            </tr>
            <?php

               }
            }
*/
            ?>
         </table>
         <br><br><br><br><br>
         <table style="border-collapse: collapse;">
            <tr>
               <td style="width: 50%; border-bottom: 1px solid #000000;">
                  <strong>SOCIETE :</strong>
               </td>
               <td style="width: 50%; border-bottom: 1px solid #000000">
                  <strong>UTILISATEUR :</strong>
               </td>
            </tr>
            <tr>
               <td style="border: 1px solid #000000; width: 50%; vertical-align: top">
                  Date : <strong><?php //echo $date->format('d/m/Y'); ?></strong><br><br>
                  Nom : <strong><?php //echo ucfirst($user->getName()); ?></strong><br><br>
                  Signature : <br><br><br><br><br>
               </td>
               <td style="border: 1px solid #000000; width: 50%; vertical-align: top;">
                  Date : <br><br>
                  Nom : <br><br>
                  Signature : <br><br><br><br><br>
               </td>
            </tr>
         </table>
         <page_footer>
            <div style="width: 100%; text-align: center; font-size: 8pt">
               - DES RÉCEPTION VEUILLEZ NOUS RETOURNER LE PRÉSENT DOCUMENT DATÉ ET SIGNÉ -
            </div>
         </page_footer>
      </page>
      <?php 

      $content = ob_get_clean();
      
      // Generate PDF with HTML2PDF lib
      $pdf = new HTML2PDF('P', 'A4', 'fr');
      $pdf->pdf->SetDisplayMode('fullpage');
      $pdf->writeHTML($content);

      $contentPDF = $pdf->Output('', 'S');

      // Store PDF in GLPi upload dir and create document
      file_put_contents(GLPI_UPLOAD_DIR . '/' . $refnumber.'.pdf', $contentPDF);
      $documents_id = self::createDocument($refnumber);

      // Add log for last generated PDF
      $export = new self();

      $input = array();
      $input['users_id']     = $users_id;
      $input['date_mod']     = date("Y-m-d H:i:s");
      $input['num']          = $num;
      $input['refnumber']    = $refnumber;
      $input['authors_id']   = Session::getLoginUserID();
      $input['documents_id'] = $documents_id;

      if($export->add($input)) {
         return true;
      }

      return false;
   }

   /**
    * Store Document into GLPi DB
    * @param refnumber
    * @return integer id of Document
    */
   static function createDocument($refnumber) {

      $doc = new Document();

      $input                          = array();
      $input["entities_id"]           = $_SESSION['glpiactive_entity'];
      $input["name"]                  = __('Used-Items-Export', 'useditemsexport').'-'.$refnumber;
      $input["upload_file"]           = $refnumber.'.pdf';
      $input["documentcategories_id"] = 0;
      $input["mime"]                  = "application/pdf";
      $input["date_mod"]              = date("Y-m-d H:i:s");
      $input["users_id"]              = Session::getLoginUserID();

      $doc->check(-1, CREATE, $input);
      $newdocid=$doc->add($input);

      return $newdocid;
   }

   /**
    * Get next num
    * @param nothing
    * @return integer
    */
   static function getNextNum() {
      global $DB;

      $query = "SELECT MAX(num) as num 
                  FROM " . self::getTable();

      $result = $DB->query($query);
      $nextNum = $DB->result($result, 0, 'num');
      if (!$nextNum) {
         return 1;
      } else {
         $nextNum++;
         return $nextNum;
      }

      return false;
   }

   /**
    * Compute next refnumber
    * @param nothing
    * @return string
    */
   static function getNextRefnumber() {
      global $DB;

      if($nextNum = self::getNextNum()) {
         $nextRefnumber = str_pad($nextNum, 4, "0", STR_PAD_LEFT);
         $date = new DateTime();
         return $nextRefnumber . '-' . $date->format('Y');
      } else {
         return false;
      }
   }

   /**
    * Get all used items for user
    * @param ID of user
    * @return array
    */
   static function getAllUsedItemsForUser($ID) {
      global $DB, $CFG_GLPI;

      $items = array();

      foreach ($CFG_GLPI['linkuser_types'] as $itemtype) {
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         if ($item->canView()) {
            $itemtable = getTableForItemType($itemtype);
            $query = "SELECT *
                      FROM `$itemtable`
                      WHERE `users_id` = '$ID'";

            if ($item->maybeTemplate()) {
               $query .= " AND `is_template` = '0' ";
            }
            if ($item->maybeDeleted()) {
               $query .= " AND `is_deleted` = '0' ";
            }
            $result    = $DB->query($query);

            $type_name = $item->getTypeName();

            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  $items[$itemtype][] = $data;
               }
            }
         }
      }

      return $items;
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
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `users_id` INT(11) NOT NULL DEFAULT '0',
                  `date_mod` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                  `num` SMALLINT(2) NOT NULL DEFAULT 0,
                  `refnumber` VARCHAR(9) NOT NULL DEFAULT '0000-0000',
                  `authors_id` INT(11) NOT NULL DEFAULT '0',
                  `documents_id` INT(11) NOT NULL DEFAULT '0',
               PRIMARY KEY  (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
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