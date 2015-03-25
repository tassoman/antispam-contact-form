how to use the exec class

# Introduction #

Now it's possible to add a pre and post custom parsing on the form adding a php class in the exec directory.

for example you can convert the results of a form in a csv and then attach the file to the email.

# Details #

Create a from called "helpme.php" and place it in the forms/ directory, then create a file called "helpme.php" and add it in the exec/ directory.

In the "exec/helpme.php" you have to create a class callend
```
class fcc_formExec_helpme extends fcc_formExec
{
```
**note that the `"fcc_formExec_"` prefix is needed to use correctly the class.**

Now take a look at the "exec\_form.php" file, this is the main class you have to extend. If you don't need a funciontality simply add an empty method on your class.

So, we want to add an attachment to the result mail, so we need to create the first two method, the first one will add an hook to the wordpress mailer funcionality and will call the second one.

```
 public function setupMailer()
 {
   add_action('phpmailer_init', array(&$this, 'execMailer'));
 }
```

the other method will parse the form input and will create the csv
```
 public function execMailer(&$phpmailer)
 {
   $csv = '';
   foreach ($this->form as $k => $v)
     $csv .= "$v;";
   $phpmailer->AddStringAttachment($csv, 'helpme.csv', 'base64', 'text/csv');
   return true;
 }
```

Because we don't need other functionality will create empty methods.

```
 public function postParser(){ }
 public function preParser(){ }
 public function execute() { }
}
```
