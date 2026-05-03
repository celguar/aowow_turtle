<?php
/*
    aowow_sql - code for generating main AoWoW database from client files
    This file is a part of AoWoW project.
    Copyright (C) 2010  Mix <ru-mangos.ru>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
  require("config.php");

  if (!isset($config['english_dbc']))
    die("Path to english DBC files is not configured");

  $dbcdir = $config['english_dbc'];

  require ("dbc2array.php");

  function dbc2array_($filename, $format)
  {
    global $dbcdir;
    if (@stat($dbcdir . $filename) == NULL) $filename = strtolower($filename);
    return dbc2array($dbcdir . $filename, $format);
  }

  function print_insert($header, $data)
  {
    $size = 0;
    echo "$header\n";
    foreach ($data as $row)
    {
      if ($size)
      {
        if ($size > 937420)
        {
          echo ";\n\n\n$header\n";
          $size = 0;
        }
        else
          echo ",\n";
      }

      // quote strings
      foreach ($row as $i => $value)
        if (!is_int($value) && !is_float($value))
          $row[$i] = "'" . str_replace("\r\n", "\\n", addslashes($value)) . "'";

      $outstr = "(" . implode(", ", $row) . ")";
      $size += strlen($outstr);
      echo $outstr;
    }
    echo ";\n\n";
  }
?>
-- Prevent data corruption
SET NAMES 'utf8';
SET SQL_MODE = '';

<?php
/*
  // Old Fog-like generation version
  $dbc = dbc2array_("AreaTable.dbc", "nxxxxxxxxxxsxxxxxxxxxxxxxxxxxxxxxxxx");
  $mapnames = array();
  foreach ($dbc as $row) $mapnames[$row[0]] = $row[1];

  $dbc = array();

  // Instance maps
  $dbc_tmp = dbc2array_("Map.dbc", "nxixsxxxxxxxxxxxxxxxxixxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx");
  foreach ($dbc_tmp as $row)
  {
    if ($row[1] > 0)
      $dbc[] = array($row[0], $row[3], $row[2], 0, 0, 0, 0);
  }

  // Regular maps
  $dbc_tmp = dbc2array_("WorldMapArea.dbc", "xiisffffxx");
  foreach ($dbc_tmp as $row)
  {
    if (isset($mapnames[$row[1]]) && !empty($mapnames[$row[1]]))
    {
      $y_min = ($row[3]<$row[4]) ? $row[3] : $row[4];
      $y_max = ($row[3]<$row[4]) ? $row[4] : $row[3];
      $x_min = ($row[5]<$row[6]) ? $row[5] : $row[6];
      $x_max = ($row[5]<$row[6]) ? $row[6] : $row[5];
      $dbc[] = array($row[0], $row[1], $mapnames[$row[1]], $x_min, $y_min, $x_max, $y_max);
    }
  }
  unset($mapnames);
*/

  $dbc = array();

  // Fog added the `type` column for something... So let's get it.
  $maptype = array();
  $areatype = array();
  $dbc_tmp = dbc2array_("Map.dbc", "nxixsxxxxxxxxxxxxxixxxxxxxxxxxxxxxxxxxxxxx");
  foreach ($dbc_tmp as $row)
  {
    $maptype[$row[0]] = $row[1];
    if ($row[3]) $areatype[$row[0] . "@" . $row[3]] = $row[1];
  }

  $dbc_temp= dbc2array_("AreaPOI.dbc", "xxxxffxxxxsxxxxxxxxxxxxxxxxxx");
  $dbc_tmp = dbc2array_("AreaTable.dbc", "niixxxxxxxisxxxxxxxxxxxxx");
  foreach ($dbc_tmp as $row_tmp)
  {
    $type = 0;
    $pointX = 0;
    $pointY = 0;
    if (isset($maptype[$row_tmp[1]]))
      $type = $maptype[$row_tmp[1]];
    if (isset($areatype[$row_tmp[1]."@".$row_tmp[0]]))
      $type = $areatype[$row_tmp[1]."@".$row_tmp[0]];
    foreach ($dbc_temp as $row_temp)
    {
      if (isset($row_temp[2]) && $row_temp[2] == $row_tmp[4])
      {
        $pointX = $row_temp[0];
        $pointY = $row_temp[1];
        break;
      }
    }
    $dbc[$row_tmp[0]] = array($row_tmp[1], $row_tmp[0], $row_tmp[4], 0, 0, 0, 0, $type);
  }

  // Update data with coords, where available niisxxxx
  $dbc_tmp = dbc2array_("WorldMapArea.dbc", "xiisffff");
  foreach ($dbc_tmp as $row)
  {
    if (isset($dbc[$row[1]]))
    {
      $dbc[$row[1]][3] = ($row[5]<$row[6]) ? $row[5] : $row[6]; // x_min
      $dbc[$row[1]][4] = ($row[3]<$row[4]) ? $row[3] : $row[4]; // y_min
      $dbc[$row[1]][5] = ($row[5]<$row[6]) ? $row[6] : $row[5]; // x_max
      $dbc[$row[1]][6] = ($row[3]<$row[4]) ? $row[4] : $row[3]; // y_max
    }
  }
  unset($dbc_temp);
  unset($dbc_tmp);
  print_insert('INSERT INTO `aowow_zones` VALUES', $dbc);

  // TODO: Get duplicates from Map.dbc automatically. Currently they are:
?>
-- Onyxia's Lair
UPDATE aowow_zones SET mapID = 249 WHERE areatableID = 2159;
-- Hall of Legends
UPDATE aowow_zones SET mapID = 450 WHERE areatableID = 2917;