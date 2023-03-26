<?php
/* Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *  \file       htdocs/core/modules/expedition/mod_expedition_safor.php
 *  \ingroup    expedition
 *  \brief      File of class to manage shipments numbering rules Safor
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';

/**
 *	Class to manage expedition numbering rules Safor
 */
class mod_expedition_safor extends ModelNumRefExpedition
{
	/**
     * Dolibarr version of the loaded document
     * @var string
     */
	public $version = 'dolibarr';

	public $prefix = 'SH';

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string Nom du modele
	 * @deprecated
	 * @see $name
	 */
	public $nom = 'Safor';

	/**
	 * @var string model name
	 */
	public $name = 'Safor';


	/**
	 *	Return default description of numbering model
	 *
	 *	@return     string      text description
	 */
    public function info()
    {
    	global $langs;
      	return $langs->trans("SimpleNumRefModelDesc", $this->prefix);
    }


	/**
	 *	Return numbering example
	 *
	 *	@return     string      Example
	 */
    public function getExample()
	{
		return $this->prefix."0501-0001";
	}


	/**
	 *	Test if existing numbers make problems with numbering
	 *
	 *	@return     boolean     false if conflit, true if ok
	 */
    public function canBeActivated()
	{
		global $conf, $langs, $db;

		$coyymm = ''; $max = '';

		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."expedition";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		$sql .= " AND entity = ".$conf->entity;

		$resql = $db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			if ($row) { $coyymm = substr($row[0], 0, 6); $max = $row[0]; }
		}
		if ($coyymm && !preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', $coyymm))
		{
			$langs->load("errors");
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		return true;
	}

	/**
	 *	Return next value
	 *
	 *	@param	Societe		$objsoc     Third party object
	 *	@param	Object		$shipment	Shipment object
	 *	@return string      			Value if OK, 0 if KO
	 */
    public function getNextValue($objsoc, $shipment)
	{
		global $db, $conf;

		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."expedition";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		$sql .= " AND entity = ".$conf->entity;

		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj) $max = intval($obj->max);
			else $max = 0;
		}
		else
		{
			dol_syslog("mod_expedition_safor::getNextValue", LOG_DEBUG);
			return -1;
		}

		$date = time();
		$yymm = strftime("%y%m", $date);

		if ($max >= (pow(10, 4) - 1)) $num = $max + 1; // If counter > 9999, we do not format on 4 chars, we take number as it is
		else $num = sprintf("%04s", $max + 1);

		dol_syslog("mod_expedition_safor::getNextValue return ".$this->prefix.$yymm."-".$num);
		return $this->prefix.$yymm."-".$num;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return next free value
	 *
	 *	@param	Societe		$objsoc     Third party object
	 *	@param	Object		$objforref	Shipment object
	 *	@return string      			Next free value
	 */
    public function expedition_get_num($objsoc, $objforref)
	{
        // phpcs:enable
		return $this->getNextValue($objsoc, $objforref);
	}
}
