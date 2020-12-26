<?php
/*
 * Basic root-level login form 12/9/2020 - initially designed for a post-style login with a header redirect as required
 * todo:
 *  handle prompt message
 *  handle error message
 *  fill in the path, querystring and fragment as needed, negotiate with what the server passes
 *  switch to an AJAX login and handle a returned redirect or callback so we can handle timeouts
 *  it would be nice if the user could say "this particular page or pages are sensitive enough that I want a hard logout if I walk away, but other pages just require me to get a modal login to continue (and possibly to submit).  This would be the cream of high-level integration
 *  MD5 the password onsubmit
 *  re-style this
 *  forgot password
 *  option to cancel or go to main/home if I'm in a modal context
 *
 */

// This is a manifest of all fields on the form for use elsewhere.
$fields = ['account', 'username', 'password', 'path', 'query', 'hash', 'password_version'];
$account = $account ?? '';
$username = $username ?? '';



?>
<form id="login" method="post" action="/Auth/login">
    <?php if(!empty($error)) { ?>
        <p style="color:darkred;"><?php echo $error;?></p>
    <?php } ?>
    <?php if(!empty($message)) { ?>
        <p style="color:darkblue;"><?php echo $message;?></p>
    <?php } ?>

    Account:            <input id="account" name="account" type="text" value="<?= $account?>" /> <br />
    User name or email: <input id="username" name="username" type="text" value="<?php $username?>" /> <br />
    Password:           <input id="password" name="password" type="password" /> <br />
                        <button id="submit" type="submit" name="submit">Submit</button>

    <input id="path" type="hidden" name="path" value="<?php echo $path;?>" />
    <input id="query" type="hidden" name="query" value="<?php echo $query;?>" />
    <input id="hash" type="hidden" name="hash" value="<?php echo $hash;?>" />
    <input id="password_version" type="hidden" name="password_version" value="" />
</form>
<script>
document.getElementById('username').focus();
if(!document.getElementById('hash').value && window.location.hash) {
	document.getElementById('hash').value = window.location.hash.replace(/^#/, '');
}
</script>