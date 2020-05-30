<?php
svn:
https://120.25.77.117:8443/svn/happyxb
feifei
togyfeifei

数据库
120.25.77.117
feifei
123456

(happyxb)

ftp
120.25.77.117
feifei
togyfeifei

(happyxb)


聚合数据
1981yangtina       YZ889900     17796016786

DELETE FROM table_name

分页要传page
 date_default_timezone_set('PRC');


<script>
var info = '{$t[content]}';
</script>
<em onclick="layer.open({content:info});">{$t['url_title']}</em>

{php
    $CI =& get_instance();
    $id = get_cookie('recycler_id');
    $data = $CI->fei->get_one('whizmo_recycler', "id = $id", '');
   
}

public function daochuAction() {
	   
	$data =  $this->dengji->get_all('dengji',''); //列表 
        if(!$data){
				$this->adminMsg(lang('没有可导出的数据'),$_SERVER['HTTP_REFERER'], 3, 0, 0);
		}
         mc_dengjidaochu('./moban/dengji.xls',1,2,$data);//1,2表示从xls第二行开始存数据

}

transform: rotate(360deg);旋转360度

<script type="text/javascript" src="http://api.map.baidu.com/api?

v=2.0&ak=ncG0sqITQzY35ZAmRdX1IhfL0XHkM9wi"></script>

function retrieve(){
	// 百度地图API功能
	var lay = layer.open({type:3,icon:1});
            var geolocation = new BMap.Geolocation();
            var gc = new BMap.Geocoder();
            geolocation.getCurrentPosition(function (r) {
                if (this.getStatus() == BMAP_STATUS_SUCCESS) {
                    var pt = r.point;
                    gc.getLocation(pt, function (rs) {
                        var address = rs.address;
                        setTimeout(function(){
                            layer.close(lay);
                            $('.addr').val(address);
                        },500);
                    });
                } else {
                    layer.close(lay);
                    layer.open({ content: "定位失败，请重试!", time: 2 });
                }
            }, { enableHighAccuracy: true });
}

public function downloadAction(){
        $file_name = $this->get('file');     //下载文件名    
        $file_dir = "D:\phpStudy\PHPTutorial\WWW\whizmo/";        //下载文件存放目录    
        //检查文件是否存在    
        if (! file_exists ( $file_dir . $file_name )) {    
            echo "文件找不到";    
            exit ();    
        } else {    
            //打开文件    
            $file = fopen ( $file_dir . $file_name, "r" );    
            //输入文件标签     
            Header ( "Content-type: application/octet-stream" );    
            Header ( "Accept-Ranges: bytes" );    
            Header ( "Accept-Length: " . filesize ( $file_dir . $file_name ) );    
            Header ( "Content-Disposition: attachment; filename=" . $file_name );    
            //输出文件内容     
            //读取文件内容并直接输出到浏览器    
            echo fread ( $file, filesize ( $file_dir . $file_name ) );    
            fclose ( $file );    
            exit ();    
        }    
    }

    