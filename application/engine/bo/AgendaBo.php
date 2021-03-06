<?php /*
	Copyright 2015-2017 Cédric Levieux, Parti Pirate

	This file is part of Congressus.

    Congressus is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Congressus is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Congressus.  If age, see <http://www.gnu.org/licenses/>.
*/

class AgendaBo {
	var $pdo = null;
	var $config = null;

	var $TABLE = "agendas";
	var $ID_FIELD = "age_id";

	function __construct($pdo, $config) {
		$this->config = $config;
		$this->pdo = $pdo;
	}

	static function newInstance($pdo, $config = null) {
		return new AgendaBo($pdo, $config);
	}

	function create(&$agenda) {
		return BoHelper::create($agenda, $this->TABLE, $this->ID_FIELD, $this->config, $this->pdo);
	}

	function update($agenda) {
		return BoHelper::update($agenda, $this->TABLE, $this->ID_FIELD, $this->config, $this->pdo);
	}

	function save(&$agenda) {
 		if (!isset($agenda[$this->ID_FIELD]) || !$agenda[$this->ID_FIELD]) {
			$this->create($agenda);
		}

		$this->update($agenda);
	}

	function delete($agenda) {
		$query = "	DELETE FROM $this->TABLE ";

		$query .= "	WHERE $this->ID_FIELD = :$this->ID_FIELD ";

		//		echo showQuery($query, $agenda);

		$args = array($this->ID_FIELD => $agenda[$this->ID_FIELD]);

		$statement = $this->pdo->prepare($query);
		$statement->execute($args);
	}

	function getById($id) {
		$filters = array($this->ID_FIELD => intval($id));

		$results = $this->getByFilters($filters);

		if (count($results)) {
			return $results[0];
		}

		return null;
	}

	function getByFilters($filters = null) {
		if (!$filters) $filters = array();
		$args = array();

		$queryBuilder = QueryFactory::getInstance($this->config["database"]["dialect"]);

		$queryBuilder->select($this->TABLE);
		$queryBuilder->addSelect("*");

		if (isset($filters["with_count_motions"]) && $filters["with_count_motions"]) {
			// TODO 
			$queryBuilder->addSelect("(SELECT COUNT(*) FROM motions WHERE mot_agenda_id = age_id AND mot_deleted = 0)", "age_number_of_motions");
		}

		if (isset($filters[$this->ID_FIELD])) {
			$args[$this->ID_FIELD] = $filters[$this->ID_FIELD];
			$queryBuilder->where("$this->ID_FIELD = :$this->ID_FIELD");
		}

		if (isset($filters["age_meeting_id"])) {
			$args["age_meeting_id"] = $filters["age_meeting_id"];
			$queryBuilder->where("age_meeting_id = :age_meeting_id");
		}

		if (isset($filters["age_parent_id"])) {
			$args["age_parent_id"] = $filters["age_parent_id"];
			$queryBuilder->where("age_parent_id = :age_parent_id");
		}

		if (isset($filters["age_label"])) {
			$args["age_label"] = $filters["age_label"];
			$queryBuilder->where("age_label = :age_label");
		}

		$queryBuilder->orderBy("age_parent_id")->orderBy("age_order");

		$query = $queryBuilder->constructRequest();
		$statement = $this->pdo->prepare($query);
//		echo showQuery($query, $args);

		$results = array();

		try {
			$statement->execute($args);
			$results = $statement->fetchAll();

			foreach($results as $index => $line) {
				foreach($line as $field => $value) {
					if (is_numeric($field)) {
						unset($results[$index][$field]);
					}
				}
			}
		}
		catch(Exception $e){
			echo 'Erreur de requète : ', $e->getMessage();
		}

		return $results;
	}
}