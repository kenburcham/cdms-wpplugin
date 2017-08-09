<?php
/*
Plugin Name: CTUIR GISWeb Extensions
Plugin URI: http://gis.ctuir.org
Description: Extensions we need for GISWeb
Version: 1.0
Author: kenburcham
Text Domain: ctuir-gisweb
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined('ABSPATH')) exit;  // if direct access

add_shortcode('display-maplist', 'display_maplist');
add_shortcode('display-adultweir', 'display_adultweir');

// register jquery and style on initialization
add_action('init', 'register_custom');
add_action('wp_enqueue_scripts', 'enqueue_custom');

function register_custom() {
    //wp_register_script( 'custom_jquery', plugins_url('/js/custom-jquery.js', __FILE__), array('jquery'), '2.5.1' );
    //wp_register_script( 'd3', 'https://d3js.org/d3.v4.min.js', array('d3'));
    wp_register_style( 'plugin_style', plugins_url('plugin.css', __FILE__), false, '1.0.0', 'all');
}

function enqueue_custom(){
    wp_enqueue_style('plugin_style');
    //wp_enqueue_script('d3');
}

function display_maplist( $atts ) {

  $ARCGIS_LOCATION = "http://www.arcgis.com/home/item.html";

  $map_type = $atts['type'];

  $args = array(
    'post_type' => 'map',
    'orderby' => 'title',
    'order' => 'ASC',
    'tax_query' => array(
        array(
            'taxonomy' => 'map-type',
            'field'    => 'slug',
            'terms'    => $map_type,
        ),
    ),
  );


  // The Query
  $the_query = new WP_Query( $args );

  ob_start();

  // The Loop
  if ( $the_query->have_posts() ) { ?>
    <div class="map-list">
      <?php while ( $the_query->have_posts() ) {
          $the_query->the_post();
          $map_image = types_render_field('map-image', array('output' => 'raw', 'size' => 'full'));
          $map_file = types_render_field('map-file', array('output' => 'raw'));
          $map_id = types_render_field('arcgis-map-id', array('output' => 'raw'));
          ?>
          <div class="map-item">
            <div class="map-item-thumbnail"><?php echo types_render_field('thumbnail'); ?></div>
            <div class="map-item-content">
              <div class="map-item-title"><h3><?= the_title(); ?></h3></div>
              <div class="map-item-description"><?= the_content(); ?></div>


              <?php if (!is_blank($map_image)){ ?>
                <div class="map-item-image"><a href="<?php echo $map_image; ?>">View Full Image</a></div>
                <?php } ?>

              <?php if (!is_blank($map_file)){ ?>
                <div class="map-item-file"><a href="<?php echo $map_file; ?>">Downloadable File</a></div>
                <?php } ?>

              <?php if (!is_blank($map_id)){ ?>
                <div class="map-item-arcgis"><a href="<?php echo $ARCGIS_LOCATION . "?id=".$map_id; ?>">Open in ArcGIS</a></div>
                <?php } ?>

          </div>
        </div>
      <?php } ?>
    </div>
    <?php
  } else {
      echo "<i>No ".$map_type." maps found.</i>";
  }
  /* Restore original Post Data */
  wp_reset_postdata();

  return ob_get_clean();

}

//call the production DB table.
function display_adultweir(){

  $mydb = new wpdb( DB_USER, DB_PASSWORD, "CDMS_PROD_LOCAL", DB_HOST );

  $sql = "select sum(TotalFishRepresented) as FishCount, Species, LocationLabel, CONVERT(date,ActivityDate) as TheDate
 from AdultWeir_vw
 where ActivityDate between DATEADD(day, -50, GETDATE()) and GETDATE()
GROUP BY LocationLabel, Species, CONVERT(date,ActivityDate)
ORDER BY thedate DESC";



  ob_start();
?>
<div class="fish-selector">
<?php
  echo "Fish running for the last ";
  ?>
  <div class="dropdown fish-dropdown">
    <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
      Month
      <span class="caret"></span>
    </button>
    <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
      <li><a href="#">Week</a></li>
      <li><a href="#">3 Days</a></li>
    </ul>
  </div>
<?php
echo " at ";
?>
<div class="dropdown fish-dropdown">
  <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
    Nursery Bridge
    <span class="caret"></span>
  </button>
  <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
    <li><a href="#">Three-mile Dam</a></li>
    <li><a href="#">Looking-glass Creek</a></li>
    <li><a href="#">Walla Walla</a></li>
  </ul>
</div>
<?php echo " and species is " ?>
<div class="dropdown fish-dropdown">
  <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
    Bull Trout
    <span class="caret"></span>
  </button>
  <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
    <li><a href="#">COHO</a></li>
    <li><a href="#">Steelhead J1</a></li>
    <li><a href="#">Steelhead J2</a></li>
  </ul>
</div>

</div>
<?php

// --- tabs --- //

?>
  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="active"><a href="#graph" aria-controls="graph" role="tab" data-toggle="tab">Graph</a></li>
    <li role="presentation"><a href="#home" aria-controls="home" role="tab" data-toggle="tab">Data</a></li>
  </ul>

<?php


echo "<div class='tab-content'>";

// --- DATA tab --- //
echo "<div role='tabpanel' class='tab-pane' id='home'>";
  $total_fish = 0;
  $color_class=" aw-highlighted";
  $current_color = '';
  $last_date = '';
  $fish_counts_array = [];

  $rows = $mydb->get_results($sql);
  echo "<table class='table table-hover'>";
  foreach ($rows as $obj) :

    //if we are a different date then toggle the color class
    if($obj->thedate != $last_date)
    {
      $current_color == $color_class ? $current_color = '' : $current_color = $color_class;
    }

    echo "<tr class='$current_color'>";
      echo "<td>".$obj->TheDate."</td>";
      echo "<td>".$obj->LocationLabel."</td>";
      echo "<td>".$obj->Species."</td>";
      echo "<td>".$obj->FishCount."</td>";
    echo "</tr>";
    $total_fish += $obj->FishCount;

    //ok for our demo -- only show the nursery bridge and BUT on the graph
    if($obj->LocationLabel == "Nursery Bridge" && $obj->Species == "BUT")
      $fish_counts_array[] = $obj;

    $last_date = $obj->thedate;
  endforeach;

  echo "</table>";

  echo "Total Fish: $total_fish";

  ?>
  <button type="button" class="btn btn-default" aria-label="Left Align">
    Download Data + Metadata
  </button>

  <?php

// --- END DATA tab --- //
echo "</div>";

// --- GRAPH tab --- //
echo "<div role='tabpanel' class='tab-pane active' id='graph'>";
echo "<svg width=\"660\" height=\"400\"></svg>";

$json_fishcounts = json_encode($fish_counts_array);
?>
<script src="//d3js.org/d3.v3.min.js"></script>

<script>
console.log("hey lets setup a graph");
var dataArray = <?php echo $json_fishcounts ?>;

var svg = d3.select("svg"),
    margin = {top: 120, right: 120, bottom: 130, left: 140},
    width = +svg.attr("width") - margin.left - margin.right,
    height = +svg.attr("height") - margin.top - margin.bottom;

svg.selectAll("rect").data(dataArray).enter().append("rect")
  .attr("class","bar")
  .attr("height",function(d,i) {return (d.FishCount*20);})
  .attr("width","40")
  .attr("x",function(d,i) {return (i*50)+60 })
  .attr("y",function(d,i) {return 350-(d.FishCount*20)} );

svg.selectAll("text").data(dataArray).enter().append("text")
  .text(function(d) {return d.FishCount})
  .attr("class","white-text")
  .attr("x", function(d, i) {return (i * 50) + 70})
  .attr("y", function(d, i) {return 365 - (d.FishCount * 20)});

    var width = 660,
              height = 400,
              padding = 50;

    var yScale = d3.scale.linear()
   	        .domain([0, 15])    // values between 0 and 100
   		.range([height - padding, padding]);   // map these to the chart height, less padding.
      //REMEMBER: y axis range has the bigger number first because the y value of zero is at the top of chart and increases as you go down.

console.dir(dataArray[0].TheDate);
console.dir(dataArray[dataArray.length-1].TheDate);

    var maxdate = new Date(dataArray[0].TheDate);
    var mindate = new Date(dataArray[dataArray.length-1].TheDate);

console.dir(mindate, maxdate);

    var xScale = d3.time.scale()
      .domain([mindate, maxdate])
      .range([padding, width-padding *2 ]);

      // define the y axis
              var yAxis = d3.svg.axis()
                  .orient("left")
                  .scale(yScale);

              // define the y axis
              var xAxis = d3.svg.axis()
                  .orient("bottom")
                  .scale(xScale);

              // draw y axis with labels and move in from the size by the amount of padding
              svg.append("g")
                  .attr("transform", "translate("+padding+",0)")
                  .call(yAxis);

              // draw x axis with labels and move to the bottom of the chart area
              svg.append("g")
                  .attr("class", "xaxis")   // give it a class so it can be used to select only xaxis labels  below
                  .attr("transform", "translate(0," + (height - padding) + ")")
                  .call(xAxis);

              // now rotate text on x axis
              // solution based on idea here: https://groups.google.com/forum/?fromgroups#!topic/d3-js/heOBPQF3sAY
              // first move the text left so no longer centered on the tick
              // then rotate up to get 45 degrees.
             svg.selectAll(".xaxis text")  // select all the text elements for the xaxis
                .attr("transform", function(d) {
                    return "translate(" + this.getBBox().height*-2 + "," + this.getBBox().height + ")rotate(-45)";
              });


</script>
<?php
// --- END GRAPH tab --- //
echo "</div>";

echo "</div>";


//make a graph, too.

  return ob_get_clean();
}


// -- change teh sort order of projects.
add_action( 'pre_get_posts', 'my_change_sort_order');
    function my_change_sort_order($query){
        if(is_post_type_archive('project')):
         //If you wanted it for the archive of a custom post type use: is_post_type_archive( $post_type )
           //Set the order ASC or DESC
           $query->set( 'order', 'ASC' );
           //Set the orderby
           $query->set( 'orderby', 'title' );
        endif;
    };









// --------------------------------- some utilities ------------------ //
//enable shortcodes in widgets
add_filter('widget_text','do_shortcode');


/**
 * Checks if a scalar value is FALSE, without content or only full of
 * whitespaces.
 * For non-scalar values will evaluate if value is empty().
 *
 * @param   mixed   $v  to test
 * @return  bool    if $v is blank
 */
function is_blank (&$v)
{
    return !isset($v) || (is_scalar($v) ? (trim($v) === '') : empty($v));
}

?>
