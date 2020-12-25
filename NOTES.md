I don't know if Server/rewrite.php changes the logic much, probably not for least amazement.

index.php is very lean and only requires a paths.php file and class before it requires bootstrap.php
What the significance of this if any is I don't know.

Bootstrap:
    - defines path constants
    - loads the APPPATH Common.php first if it's there (a blank slate by default)
    - loads the SYSTEMPATH Common.php next
        each function is shielded by function_exists
        contains mostly-smart functions which *should* operate at the function level
        view_cell looks like an interesting concept
        `Services` are mentioned such as session and logger
    - loads the APPPATH Config/Autoload.php
        this "reaches around" (see note about Autoload vs. Autoloader below) and grabs the **SYSTEMPATH** Config/AutoloadConfig which it extends - I'm not sure why this wouldn't have looked for a parallel path in `app`
        note that in CI4 namespacing, the `system` folder is `CodeIgniter` (which makes sense), and class files in all folders and subfolders in system reliably have the namespace
`CodeIgniter\Folder[\Subfolder]`.  However in the `app` folder, which has a paucity of class files, it's not so uniform: the namespace for files in the Config folder is simply `Config`, but the namespace for class files in `Controller` is `App\Controller`.  
    - loads the APPPATH Config/Modules.php
        this might be an interesting resource: http://blog.newmythmedia.com/blog/show/2016-03-15_Modules_in_CodeIgniter_4
    - loads the SYSTEMPATH Autoloader
        Autoload vs. Autoloader can be confusing; the "er" is the action.  Perhaps it should have /only/ been Autoload for the action and AutoloadConfig for anything that is not the action, but bottom line, AutoloadConfig is not the config for the action, it's the config for the Autoload (which you have to think about for a second).  Put another way, the confusion is in the two classes app/Config/Autoload.php and system/Config/AutoloadConfig.php
    - loads services
        Autoloading IS a service (CodeIgniter\Services::autoloader())
        BaseService is required first as we really don't have autoloading initialized yet.  Next app\Config\Services is required which in turn in-file requires system\Config\Services.  app\Config\Services extends system\Config\Services, and again we don't have autoloading initialized, so this works.  At this point we could call:
        
        $a = new CodeIgniter\Config\Services();
        
        and get the extended class directly, or call:
        
        $b = new Config\Services();
        
        and get the extending class (which is done in the application).
        
        
        
        
        
todo items:
in either CodeIgniter\Config\BaseConfig or Config\App need to have 
    public $moduleRouting = true;  //preferably in both
    
can you override a static method?






