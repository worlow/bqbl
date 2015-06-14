<?php
require_once "lib/lib.php";
ui_header($title="Past Champions");
?>
<style is="custom-style">
#content {
	margin: 32px;
	text-align: left;
}
paper-material {
    display: block;
    background-color: #FFFFFF;
    padding: 32px;
    margin: 32px 24px 0 24px;
}
</style>
<paper-material>
    <h2>2013 BQBL Champion</h2>
    <h4><font color='orange'>Keevon von Kevin</font></h4>
    <img src="media/kevin_front.jpg" alt="Kevin Front" style="width:172px;height:228px">
</paper-material>
<paper-material>
    <h2>2012 BQBL Champion</h2>
    <h4><font color='red'>James Hans Kristian Anderson III</font></h4>
    <img src="media/jim_front.jpg" alt="Jim Front" style="width:304px;height:228px">
    <img src="media/jim_back.jpg" alt="Jim Back" style="width:304px;height:228px">
</paper-material>
<?php
ui_footer();
?>
