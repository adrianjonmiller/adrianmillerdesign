<?php

class WPToolset_Field_Taxonomyhierarchical extends WPToolset_Field_Textfield
{
    protected $child;
    protected $names;
    protected $values = array();
    protected $valuesId = array();
    protected $objValues;

    public function init() {
        global $post;
        
        $this->objValues = array();
        $terms = wp_get_post_terms($post->ID, $this->getName(), array("fields" => "all"));
        $i = 0;
        foreach ($terms as $n => $term) {
            $this->values[] = $term->slug;
            $this->valuesId[] = $term->term_id;
            $this->objValues[$term->slug] = $term;
            $i++;
        }
        
        $all = $this->buildTerms(get_terms('category',array('hide_empty'=>0,'fields'=>'all')));

        $childs=array();
        $names=array();
        foreach ($all as $term)
        {
            $names[$term['term_id']]=$term['name'];
            if (!isset($childs[$term['parent']]) || !is_array($childs[$term['parent']]))
                $childs[$term['parent']]=array();
            $childs[$term['parent']][]=$term['term_id'];
        }

        $this->childs = $childs;
        $this->names = $names;
    }

    public function enqueueScripts() {
    }

    public function enqueueStyles() {
    }

    public function metaform() {
        $metaform = array();
        $res = $this->buildCheckboxes(0, $this->childs, $this->names, $metaform);
        $this->set_metaform($res);

        return $metaform;
    }

    private function buildTerms($obj_terms) {
        $tax_terms=array();
        foreach ($obj_terms as $term)
        {
            $tax_terms[]=array(
                'name'=>$term->name,
                'count'=>$term->count,
                'parent'=>$term->parent,
                'term_taxonomy_id'=>$term->term_taxonomy_id,
                'term_id'=>$term->term_id
            );
        }
        return $tax_terms;
    }

    private function buildCheckboxes($index, &$childs, &$names, &$metaform, $ischild=false)
    {        
        if (isset($childs[$index]))
        {
            foreach ($childs[$index] as $tid)
            {
                $name = $names[$tid];
                if (false) {
                ?>
                    <div style='position:relative;line-height:0.9em;margin:2px 0;<?php if ($tid!=0) echo 'margin-left:15px'; ?>' class='myzebra-taxonomy-hierarchical-checkbox'>
                        <label class='myzebra-style-label'><input type='checkbox' name='<?php echo $name; ?>' value='<?php echo $tid; ?>' <?php if (isset($values[$tid])) echo 'checked="checked"'; ?> /><span class="myzebra-checkbox-replace"></span>
                            <span class='myzebra-checkbox-label-span' style='position:relative;font-size:12px;display:inline-block;margin:0;padding:0;margin-left:15px'><?php echo $names[$tid]; ?></span></label>
                        <?php
                        if (isset($childs[$tid]))
                            echo $this->buildCheckboxes($tid,$childs,$names,$metaform);
                        ?>
                    </div>
                <?php
                }
                
                $metaform[] = array(
                            '#type' => 'checkbox',
                            '#title' => $names[$tid],
                            '#description' => '',
                            '#name' => $this->getName()."[]",
                            '#value' => $tid,
                            '#default_value' => in_array($tid, $this->valuesId),
                            '#attributes' => array(
                                'style' => 'float:left;'.($ischild ? 'margin-left:15px;' : '')
                            ),
                            '#validate' => @$config['validation']
                        );
                
                if (isset($childs[$tid]))
                    $metaform = $this->buildCheckboxes($tid,$childs,$names, $metaform, true);
                
            }
        }
        return $metaform;
    }
}
?>
