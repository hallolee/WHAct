/**
 * Created by cheam on 15/10/30.
 */
function view_dialg(title,content){
    var on_click = "$('#back_bg').remove();$('#__12321_dialog').remove()";
    var str ='<div id="back_bg" style="width: 100%; height:100%; position: fixed;  top: 0px;  left: 0px;  background: rgba(0, 0, 0, 0.6);  display: none;  z-index: 999;" onclick="'+on_click+'"></div>' +
        '<div id="__12321_dialog" style="width: 90%;  background: #fff;  border-radius: 4px;  margin: 0 auto;  position: absolute;  z-index: 9999;  display: none;  top: 90px;  left: 5%;  text-align: center;">' +
        '        <div style="width: 100%; height: 44px;  border-bottom: 1px solid #b2b2b2;  font-size: 1.05em;  color: #5d5d5d;  line-height: 44px;text-align: center;"><span style="margin-left: 20px;">'+title+'</span><i onclick="'+on_click+'" class="can_icon"></i></div>' +
        '    <div style="width: 85%;  margin: 0 auto;color:#999999;text-indent: 2em;text-align: left;padding: 10px 0;">' +
        content+
        '</div>' +
        '</div>';

    $("body").append(str);
    var h_height = $("body").height();
    $("#back_bg").css("height",h_height).show();
    $("#__12321_dialog").animate({ opacity: 'show' }, 500);
}

function view_img(path){
    var on_click = "$('#img_back_bg').remove();$('#__1232122_dialog').remove();$('#img_body').remove();";
    var str ='<div id="img_back_bg" style="width: 100%;height:100% ; position: fixed;  top: 0px;  left: 0px;  background: rgba(0, 0, 0, 0.6);  display: none;  z-index: 999;"></div>' +
        '<div id="img_body" style="display: table;width: 100%;height: 100%;position: absolute;z-index: 1000;top: 0;" onclick="'+on_click+'">'+
        '<div id="img_cell" style="display: table-cell;vertical-align: middle;text-align: center;width: 100%;overflow: auto;">'+
        '<img id="__1232122_dialog" style="display:none;width: 90%;height:auto;" src="'+path+'"  onclick="'+on_click+'" />' +
        '</div>'+
        '</div>';

    $("body").append(str);
    //var h_height = $("body").height();
    $("#img_back_bg").show();
    $("#__1232122_dialog").animate({ opacity: 'show' }, 500);
    //图片放大
    var img_body=document.getElementById("img_body");
    var img_cell=document.getElementById("img_cell");
    img_body.style.top=""+document.body.scrollHeight-window.innerHeight+"px";
    img_cell.style.height=""+window.innerHeight+"px";
    elements = document.getElementById("__1232122_dialog");
    pic_big(elements);
}
function pic_big(elements) {
    if (document.addEventListener) {
        var x1,x2,y1,y2,sum,startX1,startX2,startY1,startY2,startsum;
        var i=90;
        elements.addEventListener("touchstart", function (e) {
            if (e.touches.length == 2) {
                startX1 = e.touches[0].clientX;
                startX2 = e.touches[1].clientX;
                startY1 = e.touches[0].clientY;
                startY2 = e.touches[1].clientY;
                var calX=(startX2-startX1);
                var calY=(startY2-startY1);
                startsum = Math.pow((calX *calX + calY * calY), 0.5);
            }
        }, false);
        elements.addEventListener("touchmove", function (e) {
            if(e.touches.length == 1){
                if(e.preventDefault){
                    e.returnValue = true;
                    img_cell.returnValue = true;
                }
            }
            if (e.touches.length == 2) {
                e.preventDefault();
                x1 = e.touches[0].clientX;
                x2 = e.touches[1].clientX;
                y1 = e.touches[0].clientY;
                y2 = e.touches[1].clientY;
                var calX=(x2-x1);
                var calY=(y2-y1);
                sum = Math.pow((calX *calX + calY * calY), 0.5);
                if (startsum < sum && i <= 200) {
                    if (i >= 100) {
                        img_cell.scrollLeft = (img_cell.scrollWidth - img_cell.offsetWidth) / 2;
                        img_cell.scrollTop = (img_cell.scrollHeight - img_cell.offsetHeight) / 2;
                    }
                    i=i+3;
                    elements.style.width = "" + i + "%";
                } else if (startsum > sum && i >= 60) {
                    if (i >= 100) {
                        img_cell.scrollLeft = (img_cell.scrollWidth - img_cell.offsetWidth) / 2;
                        img_cell.scrollTop = (img_cell.scrollHeight - img_cell.offsetHeight) / 2;
                    }
                    i=i-3;
                    elements.style.width = "" + i + "%";
                }
            }
        }, false);
        elements.addEventListener("touchend", function (e) {
            if(e && e.preventDefault){
                e.returnValue = true;
            }
        }, false);
    }
}

function loading_view(base){
    var str ='<div id="loading_view" style="width: 100%;height:100% ; position: fixed;  top: 0px;  left: 0px;  background: rgba(0, 0, 0, 0.6);  display: none;  z-index: 999;"></div>' +
        '<img id="loading_view_img" style="position: absolute;top: 45%;left: 45%;" src="'+base+'/Application/Home/View/Public/js/UploadFile/loading.gif" />';

    $("body").append(str);
    $("#loading_view").show();
}

function remove_loading(){
    $("#loading_view").remove();
    $("#loading_view_img").remove();
}
