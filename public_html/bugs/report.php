<?php /* vim: set noet ts=4 sw=4: : */
require_once './include/prepend.inc';
require_once './include/cvs-auth.inc';

error_reporting(E_ALL ^ E_NOTICE);

/* When user submits a report, do a search and display the results before allowing
 * them to continue */

if (isset($save) && isset($pw)) { # non-developers don't have $user set
	setcookie("MAGIC_COOKIE",base64_encode("$user:$pw"),time()+3600*24*12,'/','.php.net');
}
if (isset($MAGIC_COOKIE) && !isset($user) && !isset($pw)) {
	list($user,$pw) = explode(":", base64_decode($MAGIC_COOKIE));
}

/* See bugs.sql for the table layout. */

$mail_bugs_to = "php-bugs@lists.php.net";

/*
@mysql_connect("localhost","nobody","")
	or die("Unable to connect to SQL server.");
@mysql_select_db("php3");
*/
$errors = array();
if ($in) {
	if (!($errors = incoming_details_are_valid($_POST['in'], 1))) {

		if (!$in['did_luser_search']) {

			$in['did_luser_search'] = 1;

			/* search for a match using keywords from the subject */

			$sdesc = rinse($in['sdesc']);

			/* if they are filing a feature request, only look for similar features */
			$bug_type = $in['bug_type'];
			if ($bug_type == 'Feature/Change Request') {
				$where_clause = "WHERE bug_type = '$bug_type'";
			} else {
				$where_clause = "WHERE bug_type != 'Feature/Change Request'";
			}

			list($sql_search, $ignored) = format_search_string($sdesc);

			$where_clause .= $sql_search;

			$query = "SELECT * from bugdb $where_clause LIMIT 5";

			$res = mysql_query($query) or die(htmlspecialchars($query) . "<br>" . mysql_error());

			if (mysql_num_rows($res) == 0) {
				$ok_to_submit_report = 1;
			} else {
				commonHeader("Report - Confirm");
                # the lol
                echo "<style>"; include('./style.css'); echo "</style>";

?>
<p>Are you sure that you searched before you submitted your bug report?
We found the following bugs that seem to be similar to yours; please check them
before sumitting the report as they might contain the solution you are looking for.
</p>
<p>If you're sure that your report is a genuine bug that has not been reported before,
you can scroll down and click the submit button to really enter the details into our database.
</p>


<div class="warnings">
<table class="lusersearch">
<tr>
<td><b>Description</b></td>
<td><b>Possible Solution</b></td>
</tr>
<?php

				while ($row = mysql_fetch_array($res)) {

					$resolution = mysql_get_one("SELECT comment from bugdb_comments where bug = " . $row['id'] . " order by id desc limit 1");

					if ($resolution) {
						$resolution = htmlspecialchars($resolution);
					}

					$summary = $row['ldesc'];
					if (strlen($summary) > 256) {
						$summary = htmlspecialchars(substr(trim($summary), 0, 256)) . " ...";
					} else {
						$summary = htmlspecialchars($summary);
					}

					$bug_url = "/bugs/bug.php?id=$row[id]&edit=2";

					echo "<tr><td colspan=\"2\"><a href=\"$bug_url\">Bug #" . $row['id'] . ": " . $row['sdesc'] . "</a></td></tr>";
					echo "<tr><td>" . $summary . "</td>";

					echo "<td>" . nl2br($resolution) . "</td>";

					echo "</tr>\n";

				}

?>
</table>
</div>
<?php

			}
		} else {
			/* we displayed the luser search and they said it really
			 * was not already submitted, so let's allow them to submit */
			$ok_to_submit_report = true;
		}

		if ($ok_to_submit_report) {

			/* Put all text areas together. */
			$fdesc = "Description:\n------------\n". $in['ldesc'] ."\n\n";
			if (!empty($in['repcode'])) {
				$fdesc .= "Reproduce code:\n---------------\n". $in['repcode'] ."\n\n";
			}
			if (!empty($in['expres']) || $in['expres'] === '0') {
				$fdesc .= "Expected result:\n----------------\n". $in['expres'] ."\n\n";
			}
			if (!empty($in['actres']) || $in['actres'] === '0') {
				$fdesc .= "Actual result:\n--------------\n". $in['actres'] ."\n";
			}
            
			$query = "INSERT INTO bugdb (bug_type,email,sdesc,ldesc,php_version,php_os,status,ts1,passwd) VALUES ('$in[bug_type]','$in[email]','$in[sdesc]','$fdesc','$in[php_version]','$in[php_os]','Open',NOW(),'$in[passwd]')";
			if (!$ret = mysql_query($query)) {
                die("could not insert ** $query **': " . mysql_error());
            }

			$cid = mysql_insert_id();

			$report = "";
			$report .= "From:             ".spam_protect(stripslashes($in['email']))."\n";
			$report .= "Operating system: ".stripslashes($in['php_os'])."\n";
			$report .= "PHP version:      ".stripslashes($in['php_version'])."\n";
			$report .= "PHP Bug Type:     $in[bug_type]\n";
			$report .= "Bug description:  ";

			$fdesc = stripslashes($fdesc);
			$sdesc = stripslashes($in['sdesc']);

			$ascii_report = "$report$sdesc\n\n".wordwrap($fdesc);
			$ascii_report.= "\n-- \nEdit bug report at http://pear.php.net/bugs/bug.php?id=$cid&edit=";

			//list($mailto,$mailfrom) = get_bugtype_mail($in['bug_type']);
            list($mailto, $mailfrom) = get_bugtype_mail($in['bug_type']);


			$email = stripslashes($in['email']);
			$protected_email = '"'.spam_protect($email)."\" <$mailfrom>";

			// provide shortcut URLS for "quick bug fixes"
			$dev_extra = "";
            /*
			$maxkeysize = 0;
			foreach ($RESOLVE_REASONS as $v) {
				if (!$v['webonly']) {
					$actkeysize = strlen($v['desc']) + 1;
					$maxkeysize = (($maxkeysize < $actkeysize) ? $actkeysize : $maxkeysize);
				}
			}
			foreach ($RESOLVE_REASONS as $k => $v) {
				if (!$v['webonly'])
					$dev_extra .= str_pad($v['desc'] . ":", $maxkeysize) .
						" http://bugs.php.net/fix.php?id=$cid&r=$k\n";
			}
            */

			// Set extra-headers
			$extra_headers = "From: $protected_email\n";
			$extra_headers.= "X-PHP-Bug: $cid\n";
			$extra_headers.= "X-PHP-Version: "  . stripslashes($in['php_version']) . "\n";
			$extra_headers.= "X-PHP-Category: " . stripslashes($in['bug_type'])    . "\n";
			$extra_headers.= "X-PHP-OS: "       . stripslashes($in['php_os'])      . "\n";
			$extra_headers.= "X-PHP-Status: Open\n";
			$extra_headers.= "Message-ID: <bug-$cid@bugs.php.net>";

            // mail to package developers
            mail($mailto, "#$cid [NEW]: $sdesc", $ascii_report."1\n-- \n$dev_extra", $extra_headers);
            // mail to reporter
            mail($email, "Bug #$cid: $sdesc", $ascii_report."2\n", "From: PHP Bug Database <$mailfrom>\nX-PHP-Bug: $cid\nMessage-ID: <bug-$cid@bugs.php.net>");
            header("Location: bug.php?id=$cid&thanks=4");
            exit;
        }
    } else {
	    commonHeader("Report - Problems");
	}
}

if (!isset($in)) {
    commonHeader("Report - New");
    show_bugs_menu($package);
?>

<p>Before you report a bug, make sure to search for similar bugs using the
"Bug List" link. Also, read the instructions for <a target="top" href="http://bugs.php.net/how-to-report.php">how to report a bug
that someone will want to help fix</a>.</p>

<p>If you aren't sure that what you're about to report is a bug, you should ask for help using one of the means for support
<a href="/support.php">listed here</a>.</p>

<p><strong>Failure to follow these instructions may result in your bug
simply being marked as "bogus".</strong></p>

<p><strong>If you feel this bug concerns a security issue, eg a buffer overflow, weak encryption, etc, then email
<a href="mailto:pear-group@php.net?subject=%5BSECURITY%5D+possible+new+bug%21">pear-group@php.net</a> who will assess the situation. </strong></p>

<?php
}

if ($errors) display_errors($errors);
?>
<form method="post" action="<?php echo "$PHP_SELF?package=$package";?>">
<input type="hidden" name="in[did_luser_search]" value="<?php echo $in['did_luser_search'] ? 1 : 0; ?>" />
<table>
 <tr>
  <th align="right">Your email address:</th>
  <td colspan="2">
   <input type="text" size="20" maxlength="40" name="in[email]" value="<?php echo clean($in['email']);?>" />
  </td>
 </tr><tr>
  <th align="right">PHP version:</th>
  <td>
   <select name="in[php_version]"><?php show_version_options($in['php_version']);?></select>
  </td>
 </tr><tr>
  <th align="right">Package affected:</th>
  <td colspan="2">
    <input type="hidden" name="in[bug_type]" value="<?php echo $package;?>">
    <?php echo $package;?>
    <!-- <select name="in[bug_type]"><?php //show_types($in['bug_type'],0,$package);?></select> -->
  </td>
 </tr><tr>
  <th align="right">Operating system:</th>
  <td colspan="2">
   <input type="text" size="20" maxlength="32" name="in[php_os]" value="<?php echo clean($in['php_os']);?>" />
  </td>
 </tr><tr>
  <th align="right">Summary:</th>
  <td colspan="2">
   <input type="text" size="40" maxlength="79" name="in[sdesc]" value="<?php echo clean($in['sdesc']);?>" />
  </td></tr>
 </tr><tr>
  <th align="right">Password:</th>
  <td>
   <input type="password" size="20" maxlength="20" name="in[passwd]" value="<?php echo clean($in['passwd']);?>" />
  </td>
  <td><font size="-2">
    You may enter any password here, which will be stored for this bug report.
    This password allows you to come back and modify your submitted bug report
    at a later date. <!-- [<a href="/bug-pwd-finder.php">Lost a bug password?</a>] -->
  </font></td>
 </tr>
</table>

<table>
 <tr>
  <td valign="top" colspan="2">
   <font size="-1">
   Please supply any information that may be helpful in fixing the bug:
   <ul>
    <li>A short script that reproduces the problem.</li>
    <li>The list of modules you compiled PHP with (your configure line).</li>
    <li>Any other information unique or specific to your setup.</li>
    <li>Any changes made in your php.ini compared to php.ini-dist (<b>not</b> your whole php.ini!)</li>
    <li>A <a href="bugs-generating-backtrace.php">gdb backtrace</a>.</li>
   </ul>
   </font>
  </td>
 </tr>
 <tr>
  <td valign="top">
   <b>Description:</b><br />
   <font size="-1">
   </font>
  </td>
  <td>
   <textarea cols="60" rows="15" name="in[ldesc]" wrap="physical"><?php echo clean($in['ldesc']);?></textarea>
  </td>
 </tr>
 <tr>
  <td valign="top">
   <b>Reproduce code:</b><br />
   <font size="-1">
    Please <b>do not</b> post more than 20 lines of source code.<br />
    If the code is longer then 20 lines, provide an URL to the source<br />
    code that will reproduce the bug.
   </font>
  </td>
  <td valign="top">
   <textarea cols="60" rows="15" name="in[repcode]" wrap="no"><?php echo clean($in['repcode']);?></textarea>
  </td>
 </tr>
 <tr>
  <td valign="top">
   <b>Expected result:</b><br />
   <font size="-1">
    What do you expect to happen or see when you run the code above ?<br />
   </font>
  </td>
  <td valign="top">
   <textarea cols="60" rows="15" name="in[expres]" wrap="physical"><?php echo clean($in['expres']);?></textarea>
  </td>
 </tr>
 <tr>
  <td valign="top">
   <b>Actual result:</b><br />
   <font size="-1">
    This could be a <a href="bugs-generating-backtrace.php">backtrace</a> for example.<br />
    Try to keep it as short as possible without leaving anything relevant out.
   </font>
  </td>
  <td valign="top">
   <textarea cols="60" rows="15" name="in[actres]" wrap="physical"><?php echo clean($in['actres']);?></textarea>
  </td>
 </tr>
 <tr>
  <td colspan="2">
   <div align="center"><input type="submit" value="Send bug report" /></div>
  </td>
 </tr>
</table>
</form>
<?php
commonFooter();
