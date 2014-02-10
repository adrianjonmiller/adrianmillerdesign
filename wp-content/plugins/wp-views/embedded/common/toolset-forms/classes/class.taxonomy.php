<?php
require_once 'class.textfield.php';

class WPToolset_Field_Taxonomy extends WPToolset_Field_Textfield
{
    public $values = "";
    public $objValues;
    
    public function init() {
        global $post;
        
        $this->objValues = array();
        $terms = wp_get_post_terms($post->ID, $this->getName(), array("fields" => "all"));
        $i = 0;
        foreach ($terms as $n => $term) {
            $this->values .= ($i==0) ? $term->slug : ",".$term->slug;
            $this->objValues[$term->slug] = $term;
            $i++;
        }
        
        wp_register_style('wptoolset-taxonomy', 
                WPTOOLSET_FORMS_RELPATH.'/css/taxonomy.css');
        
        wp_register_script( 'wptoolset-jquery-autocompleter',
                WPTOOLSET_FORMS_RELPATH . '/js/jquery.autocomplete.js',
                array('jquery'), WPTOOLSET_FORMS_VERSION );
        
        wp_register_style('wptoolset-autocompleter', WPTOOLSET_FORMS_RELPATH.'/css/autocompleter.css');
                        
        add_action( 'wp_footer', array($this, 'javascript_autocompleter') );
    }
    
    public function enqueueScripts() { 
        wp_enqueue_script('jquery');
        wp_enqueue_script( 'wptoolset-jquery-autocompleter' );
    }

    public function enqueueStyles() {
        wp_enqueue_style('wptoolset-taxonomy');
        wp_enqueue_style('wptoolset-autocompleter');
        wp_print_styles();
    }
       
    public function javascript_autocompleter() {            
            $autosubmit = 'function onSelectItem(row)
                           {
                                jQuery("input#'.$this->getName().'").focus();
                           }';
            $extra = '
                            function formatItem(row) {                            
                                    return row[0];
                            }
                            function formatItem2(row) {
                                if(row.length == 3){
                                    var attr = "attr=\"" + row[2] + "\"";
                                } else {
                                    attr = "";
                                }
                                return "<span "+attr+">" + row[1] + " matches</span>" + row[0];
                            }';
            $results = 1;
            echo '<script type="text/javascript">
                    function setTaxonomy() {
                        var tmp_tax = jQuery("input[name=tmp_'.$this->getName().']").val();
                        if (tmp_tax.trim()=="") return;
                        console.log(tmp_tax);
                        var tax = jQuery("input[name='.$this->getName().']").val();
                        console.log(tax);
                        var arr = tax.split(",");
                        if (jQuery.inArray(tmp_tax, arr)!==-1) return;
                        var toadd = (tax=="") ? tmp_tax : tax+","+tmp_tax;
                        console.log(toadd);
                        jQuery("input[name='.$this->getName().']").val(toadd);
                        jQuery("input[name=tmp_'.$this->getName().']").val(""); 
                        updateTaxonomies();
                    }
                    
                    function updateTaxonomies() {                       
                        var taxonomies = jQuery("input[name='.$this->getName().']").val();
                        if (taxonomies.trim()=="") return;
                        console.log(taxonomies);
                        var toshow = taxonomies.split(",");
                        console.log(toshow);
                        var str = "";
                        for (var i=0;i<toshow.length;i++) {
                            var sh = toshow[i].trim();
                            console.log(sh);
                            str += "<span><a class=\"ntdelbutton\" rel=\""+i+"\" id=\"post_tag-check-num-"+i+"\">X</a>&nbsp;"+sh+"</span>";
                            console.log(str);
                        }
                        jQuery("div.tagchecklist").html(str);
                        
                        jQuery(".ntdelbutton").click(function() {                            
                            var n = jQuery(this).attr("rel");
                            var taxonomies = jQuery("input[name='.$this->getName().']").val();
                            var arr = taxonomies.split(",");
                            var newstr = "";
                            var newstr4tax = "";
                            var counter = 0;
                            for (var i=0;i<arr.length;i++) {                            
                                if (i!=n) {
                                    var sh = arr[i].trim();
                                    newstr += "<span><a class=\"ntdelbutton\" rel=\""+i+"\" id=\"post_tag-check-num-"+i+"\">X</a>&nbsp;"+sh+"</span>"; 
                                    newstr4tax += (counter==0) ? sh : ","+sh;
                                    counter++;
                                }                                
                            }
                            jQuery("input[name='.$this->getName().']").val(newstr4tax);
                            jQuery("div.tagchecklist").html("");
                            jQuery("div.tagchecklist").html(newstr);
                        });
                    }
                    
                    function initTaxonomies(values) {
                        jQuery("div.tagchecklist").html(values);
                        jQuery("input[name='.$this->getName().']").val(values);
                        updateTaxonomies();
                    }
                    
                    jQuery(document).ready(function() {
                            initTaxonomies("'. $this->values .'");

                            jQuery("input[name=tmp_'.$this->getName().']").autocomplete(
                                    "'.WPTOOLSET_FORMS_RELPATH.'/external/autocompleter.php",
                                    {
                                        delay:10,
                                        minChars:2,
                                        matchSubset:1,
                                        matchContains:1,
                                        cacheLength:10,
                                        formatItem:formatItem,
                                        onItemSelect:onSelectItem,
                                        autoFill:true
                                    }
                            );
                    });

                    '.$autosubmit.'
                    '.$extra.'
            </script>';
    }
    
    public function metaform() {        
        $metaform = array();
        $metaform[] = array(
            '#type' => 'hidden',
            '#title' => '',
            '#description' => '',
            '#name' => $this->getName(),
            '#value' => $this->getValue(),
            '#attributes' => array(
                'style' => 'float:left'
            ),
            
            '#validate' => $this->getValidationData()
        );
        $metaform[] = array(
            '#type' => 'textfield',
            '#title' => '',
            '#description' => '',
            '#name' => "tmp_".$this->getName(),
            '#value' => $this->getValue(),
            '#attributes' => array(
                'style' => 'float:left'                
            ),
            '#before' => '<span style="float:left;">'.$this->getTitle().'</span>',
            '#validate' => $this->getValidationData()
        );
        $metaform[] = array(
            '#type' => 'button',
            '#title' => '',
            '#description' => '',
            '#name' => "btn_".$this->getName(),
            '#value' => 'add',
            '#attributes' => array(
                'style' => 'float:left',
                'onclick' => 'setTaxonomy()'
            ),
            
            '#after' => '<div style="clear:both;"></div><div class="tagchecklist"><span><a class="ntdelbutton" id="post_tag-check-num-0">X</a>&nbsp;test</span></div><div style="clear:both;">',
            '#validate' => $this->getValidationData()
        );
        $this->set_metaform($metaform);       
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

    private function buildCheckboxes($index, &$childs, &$names)
    {
        if (isset($childs[$index]))
        {
            foreach ($childs[$index] as $tid)
            {
                $name = $names[$tid];
                ?>
                <div style='position:relative;line-height:0.9em;margin:2px 0;<?php if ($tid!=0) echo 'margin-left:15px'; ?>' class='myzebra-taxonomy-hierarchical-checkbox'>
                    <label class='myzebra-style-label'><input type='checkbox' name='<?php echo $name; ?>' value='<?php echo $tid; ?>' <?php if (isset($values[$tid])) echo 'checked="checked"'; ?> /><span class="myzebra-checkbox-replace"></span>
                        <span class='myzebra-checkbox-label-span' style='position:relative;font-size:12px;display:inline-block;margin:0;padding:0;margin-left:15px'><?php echo $names[$tid]; ?></span></label>
                    <?php
                    if (isset($childs[$tid]))
                        echo $this->buildCheckboxes($tid,$childs,$names);
                    ?>
                </div>
            <?php
            }
        }
    }    
}
?>
