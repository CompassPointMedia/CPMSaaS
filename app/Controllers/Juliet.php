<?php namespace App\Controllers;

/**
 * Class Juliet
 * @package App\Controllers
 *
 * This is a very different approach and doesn't need to use any physical view templates to operate.
 * 2019-09-18
 * ----------
 * There are several things going on here:
 * 1. we want to be able to buffer the whole build and have later/more nested nodes touch parent nodes
 * 2. we want to conjoin pages in to one page
 *      what block do they go in
 *      do they have their own template
 *      then example.com/contact-us?ref=18348 should be the equivalent canonically to example.com/?ref=18348#contact-us
 * 3. where does caching fit in?  i.e. where does it fit in with the existing view() method and ->render()
 *      also caching is modular so see how you can add your own
 * 4. what if we just want an excerpt
 * 5. what if we wanted JSON vs. HTML?
 *
 *
 */
class Juliet extends BaseController
{

    protected $cnx;


    protected $thispage;

    protected $thisfolder;

    protected $thissubfolder;

    protected $thisnode;

    protected $modules = [];



    public function __construct()
    {

        /**
         * So, on on construct I want to make sure all of my Juliet assets are in place, or relegate this to a module class which determines this.
         *
         * If the site needs say a paid account to run, that needs to be injected here
         *
         * Juliet also needs to handle remote API calls, so perhaps /api/ can be reserved
         *
         * Maybe route the $modules to Services::module('my_module');
         */
        if ($a = func_get_args())
        {
            foreach ($a as $idx => $module)
            {

            }
        }
        prn($a = func_get_args());
        prn(get_included_files());

        $this->cnx = \Config\Database::connect();
    }

    public function getGroup(){
        return 47;
    }



    public function renderPage($thisnode)
	{

	    /*
	     * Juliet first determines a template and then maps content into the template via a pecking order (of modules), and then renders it.
	     * A template can be the default template or the module's specification
	     *
	     *
	     *
	     */
	    $cnx = $this->cnx;

	    /* so the logic is that we have a default GROUP, which informs us as to the template; the group is synonymous with a navigation schema but I suppose it doesn't have to be */

	    //$template = new

	    //what group are we working with?
        $groupid = $this->getGroup();

        //what template id are we working with?  Is this page conjoined with others and if so what Block/SubTemplate are we using?
        //@todo better query to get where I am in conjoined pages
        $sql = "SELECT
        jp.Child_ID AS JoinParentChild_ID,
        jp.Templates_ID AS JoinParentTemplate_ID,
        jc.Child_ID AS JoinChildParent_ID,
        jc.Templates_ID AS JoinChildTemplate_ID,
        h.ID AS Hierarchy_ID,
        h.Templates_ID AS ParentTemplate,
        COALESCE(jp.Blocks_ID, jc.Blocks_ID) AS Blocks_ID
        FROM 
        gen_nodes n JOIN gen_nodes_hierarchy h ON n.ID = $thisnode AND h.Nodes_ID = n.ID AND h.GroupNodes_ID = $groupid
        LEFT JOIN gen_conjoins jp ON h.id = jp.Parent_ID
        LEFT JOIN gen_conjoins jc ON h.id = jc.Child_ID
        GROUP BY n.ID
        ";
        $result = $cnx->query($sql);
        if (! ($result = $result->getResultArray()))
        {
            //@todo throw exception
            exit('no result for node '. $thisnode . ' on default group ' . $groupid);
        }
        extract($result[0]);

        if ($ChildTemplate && ! $Blocks_ID)
        {
            //@todo: see if we can determine a default main content block to conjoin the page(s) within
        }


        // now that we have the template id, get the heirarchy of that template and any sub-template
        $structure = $this->getStructure($ParentTemplate);
        $childStructure = $this->getStructure($ChildTemplate);

        prn(($structure));




	}

	public function getStructure($templateId, $parentId = null)
    {
        if (! $templateId || $templateId == '0')
        {
            return [];
        }
        $sql = "SELECT * FROM gen_templates_blocks WHERE Templates_ID = $templateId AND Blocks_ID " . ($parentId ? ' = ' . $parentId : ' IS NULL');
        if (! $result = $this->cnx->query($sql))
        {
            return [];
        }
        $results = $result->getResultArray();
        foreach($results as $n => $v)
        {
            $results[$n]['children'] = $this->getStructure($templateId, $v['ID']);
        }
        return $results;
    }

    public function getLayout($structure, $options = [])
    {

    }

	//--------------------------------------------------------------------

}
