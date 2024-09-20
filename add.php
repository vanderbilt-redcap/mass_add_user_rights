<?php

use \Vanderbilt\MassAddUserRights;

require_once(__DIR__."/MassAddUserRights.php");

echo "
<head>
<title>Add User Rights en Masse</title>
</head>

<style>
body { font-family: Arial, Helvetica, sans-serif;  font-size: 1.2em; }
p { max-width: 800px; padding: 4px; }
.red { background-color: #9A2A2A; }
.green { background-color: #008000; }
button { font-size: 1.2em; }
input[type=text] { font-size: 1.2em; }
</style>";
$mimicUsername = $module->escape($_POST['mimicUsername'] ?? "");
$url = $module->getUrl("add.php");
if (($_POST['username'] ?? "") && !empty($_POST['projects'] ?? []) && ($_POST['mimicUsername'] ?? "")) {
    $username = $module->escape($_POST['username']);
    $pids = $module->escape($_POST['projects']);
    if ($module->doesUserExist($username)) {
        $rowsUpdated = $module->addUserRights($username, $mimicUsername, $pids);
        if ($rowsUpdated > 0) {
            echo "<p class='green'>$rowsUpdated projects successfully updated for $username.</p>";
        } else {
            echo "<p class='red'>No rows added. The user $username might have already been added to these projects.</p>";
        }
    } else {
        echo "<p class='red'>No rows added. The user $username does not exist.</p>";
    }
} else if ($_POST['username'] ?? "") {
    echo "<p class='red'>No projects checked.</p>";
} else if (!empty($_POST['projects'] ?? [])) {
    echo "<p class='red'>No user specified.</p>";
}
if ($mimicUsername) {
    $projectNames = $module->getProjectNames($module->getUserProjects($mimicUsername));
    $lines = [];
    if (empty($projectNames)) {
        $lines[] = "You don't have rights to any projects.";
    } else {
        $sortAlphabetically = (($_POST['sort'] ?? "alphabetically") == "alphabetically");
        if ($sortAlphabetically) {
            asort($projectNames);
        } else {
            ksort($projectNames, SORT_NUMERIC);
        }
        foreach ($projectNames as $pid => $projectName) {
            $lines[] = "<input type='checkbox' id='project_$pid' name='projects[]' value='$pid'/><label for='project_$pid'> $projectName</label>";
        }
    }

    echo "<h1>Step 2: Add a User to Several Projects for $mimicUsername</h1>";
    echo "<p>Input a username to add to projects, and check off which of the projects for $mimicUsername should be impacted. If the user has already been added to the project, no changes will be made. The user rights for $mimicUsername for each project will be used for the new username on that project.</p>";
    echo "<form action='$url' method='POST'>";
    echo "<input type='hidden' name='redcap_csrf_token' value='{$module->getCSRFToken()}' />";
    echo "<input type='hidden' name='mimicUsername' value='$mimicUsername' />";
    echo "<p><label for='username'>Username to Add to Projects: </label><input type='text' id='username' name='username' /></p>";
    echo "<p>".implode("<br/>", $lines)."</p>";
    echo "<p><button>Add Username to Projects</button></p>";
    echo "</form>";
    echo "<hr/>";
}

$alphabeticallyChecked = (($_POST['sort'] ?? "alphabetically") == "alphabetically") ? "checked" : "";
$numericallyChecked = ($alphabeticallyChecked != "checked") ? "checked" : "";
$defaultUsername = $mimicUsername ?: $module->getCurrentUsername();
echo "<h1>Step 1: Choose a User to Mimic as a Template</h1>";
echo "<form action='$url' method='POST'>";
echo "<input type='hidden' name='redcap_csrf_token' value='{$module->getCSRFToken()}' />";
echo "<p><label for='mimicUsername'>Username to Mimic as a Template: </label><input type='text' id='mimicUsername' name='mimicUsername' value='$defaultUsername' /></p>";
echo "<p>
    <input type='radio' id='sortAlphabetically' name='sort' value='alphabetically' $alphabeticallyChecked /><label for='sortAlphabetically'> Sort Projects Alphabetically</label>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <input type='radio' id='sortNumerically' name='sort' value='numerically' $numericallyChecked /><label for='sortNumerically'> Sort Projects Numerically</label>
</p>";
echo "<p><button>Search for this User's Projects</button></p>";
echo "</form>";
