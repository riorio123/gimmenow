require(['jquery', 'jquery/ui'], function($){
    var $m = $.noConflict();
    $m(document).ready(function() {

        $m("#lk_check1").change(function(){
            if($("#lk_check2").is(":checked") && $("#lk_check1").is(":checked")){
                $("#activate_plugin").removeAttr('disabled');
            }
        });

        $m("#lk_check2").change(function(){
            if($("#lk_check2").is(":checked") && $("#lk_check1").is(":checked")){
                $("#activate_plugin").removeAttr('disabled');
            }
        });

        $m(".navbar a").click(function() {
            $id = $m(this).parent().attr('id');
            setactive($id);
            $href = $m(this).data('method');
            voiddisplay($href);
        });

        $m(".btn-link").click(function() {
            $m(".show_info").slideUp("slow");
            if (!$m(this).next("div").is(':visible')) {
                $m(this).next("div").slideDown("slow");
            }
        });
        $m('#idpguide').on('change', function() {
            var selectedIdp =  jQuery(this).find('option:selected').val();
            $m('#idpsetuplink').css('display','inline');
            $m('#idpsetuplink').attr('href',selectedIdp);
        });
        $m("#mo_saml_add_shortcode").change(function(){
            $m("#mo_saml_add_shortcode_steps").slideToggle("slow");
        });
        $m('#error-cancel').click(function() {
            $error = "";
            $m(".error-msg").css("display", "none");
        });
        $m('#success-cancel').click(function() {
            $success = "";
            $m(".success-msg").css("display", "none");
        });
        $m('#cURL').click(function() {
            $m(".help_trouble").click();
            $m("#cURLfaq").click();
        });
        $m('#help_working_title1').click(function() {
            $m("#help_working_desc1").slideToggle("fast");
        });
        $m('#help_working_title2').click(function() {
            $m("#help_working_desc2").slideToggle("fast");
        });

    });
});

function setactive($id) {
    $m(".navbar-tabs>li").removeClass("active");
    $id = '#' + $id;
    $m($id).addClass("active");
}

function voiddisplay($href) {
    $m(".page").css("display", "none");
    $m($href).css("display", "block");
}

function mosp_valid(f) {
    !(/^[a-zA-Z?,.\(\)\/@ 0-9]*$/).test(f.value) ? f.value = f.value.replace(/[^a-zA-Z?,.\(\)\/@ 0-9]/, '') : null;
}

function showTestWindow() {
    var myWindow = window.open(testURL, "TEST OAUTH", "scrollbars=1 width=800, height=600");
}

function mooauth_upgradeform(planType){
    jQuery('#requestOrigin').val(planType);
    jQuery('#mocf_loginform').submit();
}

function ifUserRegistered(){
    if (document.getElementById('registered').checked){
        jQuery('#confirmPassword').css('display','none');
        jQuery('#firstName').css('display','none');
        jQuery('#lastName').css('display','none');
        jQuery('#company').css('display','none');
    } else {
        jQuery('#confirmPassword').css('display','block');
        jQuery('#firstName').css('display','block');
        jQuery('#lastName').css('display','block');
        jQuery('#company').css('display','block');
    }

}
function supportAction(){
}
function hide_show_GrantType(element) {
    document.getElementById("hideValuesOnSelect").style.display = element.value == "implicit_grant" ? "block" : "none";
    document.getElementById("hideValuesOnSelect").style.display = element.value == "hybrid_grant" ? "block" : "none";
    document.getElementById("hideValuesOnSelect").style.display = element.value == "password_grant" ? "block" : "none";
    document.getElementById("hideValuesOnSelect").style.display = element.value == "client_credentials_grant" ? "block" : "none";
    document.getElementById("hideValuesOnSelect").style.display = element.value == "authorization_code" ? "none" : "block";
 }
 function ShowHideDiv() {
    var chk_url = document.getElementById("chk_url");

      var endpoint_url = document.getElementById("endpoint_url");
       endpoint_url.style.display = chk_url.checked ? "block" : "none";


          var chk_manual = document.getElementById("chk_manual");


          var mo_oauth_authorize_url= document.getElementById("mo_oauth_authorize_url");
   mo_oauth_authorize_url.style.display = chk_manual.checked ? "block" : "none";


}
