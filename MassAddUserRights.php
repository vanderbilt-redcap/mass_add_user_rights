<?php

namespace Vanderbilt\MassAddUserRights;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class MassAddUserRights extends AbstractExternalModule {
    public function getUserProjects(string $username): array {
        $sql = "SELECT project_id FROM redcap_user_rights WHERE username = ? AND user_rights = 1";
        $result = $this->query($sql, [$username]);
        $pids = [];
        while ($row = $result->fetch_assoc()) {
            $pids[] = $this->escape($row['project_id']);
        }
        return $pids;
    }

    public function getCurrentUsername(): string {
        return $this->getUser()->getUsername();
    }

    public function getProjectNames(array $pids): array {
        $questionMarks = array_fill(0, count($pids), "?");
        $sql = "SELECT project_id, app_title FROM redcap_projects WHERE project_id IN (" . implode(',', $questionMarks) . ")";
        $result = $this->query($sql, $pids);
        $projectNames = [];
        while ($row = $result->fetch_assoc()) {
            $projectNames[$this->escape($row['project_id'])] = $this->escape($row['app_title']);
        }
        return $projectNames;
    }

    public function doesUserExist(string $username): bool {
        $sql = "SELECT ui_id FROM redcap_user_information WHERE username = ?";
        $result = $this->query($sql, [$username]);
        return ($result->num_rows > 0);
    }

    # returns the number of rows/projects inserted
    public function addUserRights(string $username, string $templateUsername, array $pids): int {
        $questionMarks = array_fill(0, count($pids), "?");
        $sql = "SELECT project_id FROM redcap_user_rights WHERE username = ? AND project_id IN (".implode(",", $questionMarks).")";
        $params = array_merge([$username], $pids);
        $userProjectResult = $this->query($sql, $params);
        # these pids will not be inserted
        $userExistingPids = [];
        while ($row = $userProjectResult->fetch_assoc()) {
            $userExistingPids[] = $this->escape($row['project_id']);
        }

        # if a template user doesn't have rights (i.e., if $pids has a value that they don't have access to),
        # it will be skipped
        $sql = "SELECT * FROM redcap_user_rights WHERE username = ? AND project_id IN (".implode(",", $questionMarks).")";
        $params = array_merge([$templateUsername], $pids);
        $templateUserResult = $this->query($sql, $params);
        $newRows = [];
        $fields = [];
        while ($row = $templateUserResult->fetch_assoc()) {
            if (!in_array($row['project_id'], $userExistingPids)) {
                $newRow = [];
                foreach ($row as $field => $value) {
                    if ($field == "username") {
                        $newRow[$field] = $username;
                    } else if ($field == "api_token") {
                        $newRow[$field] = NULL;
                    } else if (isset($value)) {
                        $newRow[$field] = $this->escape($value);
                    } else {
                        $newRow[$field] = NULL;
                    }
                }
                $fields = array_keys($newRow);
                $newRows[] = $newRow;
            }
        }

        if (!empty($fields) && !empty($newRows)) {
            $rowsOfQuestionMarks = [];
            $params = [];
            foreach ($newRows as $row) {
                $questionMarks = array_fill(0, count($fields), "?");
                $rowsOfQuestionMarks[] = "(".implode(",", $questionMarks).")";
                foreach ($fields as $field) {
                    $params[] = $row[$field];     // do not provide a default value so that null values are stored
                }
            }
            $sql = "INSERT INTO redcap_user_rights (`".implode("`,`", $fields)."`) VALUES ".implode(",", $rowsOfQuestionMarks);
            echo $sql."<br/>";
            $this->query($sql, $params);
            return count($newRows);
        } else {
            return 0;
        }
    }
}