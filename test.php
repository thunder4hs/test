<?php
require_once("jpgraph2/jpgraph.php");
require_once("jpgraph2/jpgraph_line.php");
//require_once("jpgraph2/jpgraph_utils.inc.php");

require "define.php";

if(isset($_GET["p"]))
	$page = $_GET["p"];
else
	$page = "0";

if(isset($_GET["s"]))
	$show = $_GET["s"];
else
	$show = "0";
	
	
		$tickLabels = array();
		$tickPositions = array();
		
		//X軸目盛の設定
		if($show == 0){
			$graph_title = '年間表示';
			
			$today = strtotime( "today" );
			$kiten = strtotime("-". $page . " year" , $today);
			$next_month = strtotime("+1 month" , $kiten);
			$n_year = date("Y", $next_month);
			$n_month = date("m", $next_month);
			$endStamp = strtotime($n_year.'/'.$n_month.'/01') ;	
			$startStamp = strtotime("-1 year" , $endStamp);
			
			
			for($a = 0; $a < 13; $a++) {	
			//------------------------------------------- X軸のラベルを配列に格納する。1月だけ西暦年を表示する。
				$tuki = date("n", strtotime($a." month" ,$startStamp));
				$year = date("Y", strtotime($a." month" ,$startStamp));
				
				$label = "";
				if($a==0 || $tuki == 1){
					$label.= $year."/";
				}
				
				$label.= $tuki;
				
				$tickLabels[$a] = $label;
			//------------------------------------------- X軸のラベルの位置を配列に格納する。
				$tickDate = date("Y/m/d", strtotime($a." month" ,$startStamp));
				$tickPositions[$a] = strtotime($tickDate);
			}
			
			//X軸余白
			$grace = 100000;
		
		}else if($show == 1){
			
			$graph_title = '月間表示';
			
			$today = strtotime( "today" );
			$kiten = strtotime("-". $page . " month" , $today);
			$endStamp = strtotime("+1 day" , $kiten);
			$startStamp = strtotime("-1 month" , $endStamp);
			$count_day = ($endStamp - $startStamp) / 86400;


			$tickLabels = array();
			$tickPositions = array();
			for($a = 0; $a < $count_day+1; $a++) {	
				
				$hi = date("j", strtotime($a." day" ,$startStamp));
				$tuki = date("n", strtotime($a." day" ,$startStamp));
				$year = date("Y", strtotime($a." day" ,$startStamp));
				
				$label = "";
				if( ($a==0) || ($tuki == 1 && $hi == 1)){
					$label = $year."\n";
				}
				if($a==0 || $hi == 1){
					$label .= $tuki."/";
				}
				
				$label .= $hi;
				
				$tickLabels[$a] = $label;

				$tickDate = date("Y/m/d", strtotime($a." day" ,$startStamp));
				$tickPositions[$a] = strtotime($tickDate);
			}
			
			//X軸余白
			$grace = 20000;
			
		}else if($show == 2){
			
			$graph_title = '週間表示';
			
			$today = strtotime( "today" );
			$kiten = strtotime("-". $page . " week" , $today);
			$endStamp = strtotime("+1 day" , $kiten);
			$startStamp = strtotime("-1 week" , $endStamp);

			$count_day = ($endStamp - $startStamp) / 86400;


			$tickLabels = array();
			$tickPositions = array();
			for($a = 0; $a < $count_day+1; $a++) {	
				
				$hi = date("j", strtotime($a." day" ,$startStamp));
				$tuki = date("n", strtotime($a." day" ,$startStamp));
				$year = date("Y", strtotime($a." day" ,$startStamp));
				
				$label = "";
				if( ($a==0) || ($tuki == 1 && $hi == 1)){
					$label = $year."\n";
				}
				if($a==0 || $hi == 1){
					$label .= $tuki."/";
				}
				
				$label .= $hi;
				
				$tickLabels[$a] = $label;

				$tickDate = date("Y/m/d", strtotime($a." day" ,$startStamp));
				$tickPositions[$a] = strtotime($tickDate);
			}
			
			//X軸余白
			$grace = 20000;
		}
		
		
		$endDate = date("Y-m-d H:i:s", $endStamp);
        $startDate = date("Y-m-d H:i:s", $startStamp);


//データ読み込み
$dbcon = mysql_connect(MYSQL_SERVER ,MYSQL_USER ,MYSQL_PASS); #接続

if (!$dbcon) 
	die('接続に失敗しました。');

$selected_db = mysql_select_db(MYSQL_DB);

if (!$selected_db) 
	die('データベースの選択に失敗しました。');

$sql = "
SELECT timestamp, type, value1,value2
FROM graph_apps
ORDER BY timestamp ASC
";

$result = mysql_query ( $sql );


		$datay_spo2 = array();
		$datax_spo2 = array();
		
		$datay_bp1 = array();
		$datax_bp1 = array();
		
		$datay_bp2 = array();
		$datax_bp2 = array();
		
		$datay_bt = array();
		$datax_bt = array();



		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			$timestamp = strtotime($row["timestamp"]);
        	$type = $row["type"];
        	$value1 = $row["value1"];
        	$value2 = $row["value2"];
        	
        	if(strcmp($type, "spo2")==0){
		  		array_push($datay_spo2, $value1);
		  		array_push($datax_spo2, $timestamp);
			}else if(strcmp($type, "bp")==0){
				array_push($datay_bp1, $value1);
		  		array_push($datax_bp1, $timestamp);
		  		
		  		array_push($datay_bp2, $value2);
		  		array_push($datax_bp2, $timestamp);
			}else if(strcmp($type, "bt")==0){
        		array_push($datay_bt, $value1);
        		array_push($datax_bt, $timestamp);
        	}
		}

mysql_close($dbcon);

		$xmin = $startStamp - $grace;
		$xmax = $endStamp + $grace;
		
		$graph = new Graph(900,600);
		$graph->img->SetImgFormat("png");
		$graph->SetScale('textlin',0,0,$xmin,$xmax);
		//$graph->SetY2Scale('lin',0,0);
		$graph->SetFrame(false);
		$graph->yscale->ticks->Set(20, 10);
		$graph->img->SetMargin(50,10,70,10);
		
		$graph->title->Set($graph_title);
		$graph->title->SetFont(FF_MINCHO,FS_NORMAL,12);
		$graph->xaxis->SetPos('min');
		$graph->xaxis->SetMajTickPositions($tickPositions,$tickLabels);
		$graph->xaxis->SetFont(FF_GOTHIC,FS_NORMAL,10);
		
		$graph->xaxis->title->Set("日");
		$graph->xaxis->title->SetFont(FF_GOTHIC,FS_NORMAL,10);
		//$graph->yaxis->title->Set("値");
		//$graph->yaxis->title->SetFont(FF_GOTHIC,FS_NORMAL,10);
		
		//$graph->yaxis->HideLine(true);

		// 凡例
		$graph->legend->SetPos(0.5, 0.05, "center", "top");
		$graph->legend->SetLayout(LEGEND_HOR);
		$graph->legend->SetFont(FF_GOTHIC, FS_NORMAL);
		//$graph->legend->SetShadow(true);
		$graph->legend->SetLineWeight(1);
		$graph->legend->SetColor('black','darkgray');
		$graph->legend->SetFillColor('lightblue');

		$graph->xgrid->Show(true);
		$graph->xgrid->SetLineStyle("solid");
		
		$p1 = new LinePlot($datay_bt,$datax_bt);
		$p1->SetLegend("体温(%)");
		$p1->mark->SetType(MARK_UTRIANGLE);
		$p1->mark->SetFillColor("blue");
		$p1->mark->SetWidth(2);
		$p1->SetColor('blue');
		//$p1->value->Show();
		$graph->Add($p1);


		$p2 = new LinePlot($datay_spo2,$datax_spo2);
		$p2->SetLegend("SpO2値(%)");
		$p2->mark->SetType(MARK_FILLEDCIRCLE);
		$p2->mark->SetFillColor("red");
		$p2->mark->SetWidth(2);
		$p2->SetColor('red');
		$graph->Add($p2);
		
		
		$p3 = new LinePlot($datay_bp1,$datax_bp1);
		$p3->SetLegend("最高血圧(mmHg)");
		$p3->mark->SetType(MARK_SQUARE);
		$p3->mark->SetFillColor("green");
		$p3->mark->SetWidth(4);
		$p3->SetColor("green");
		$graph->Add($p3);
		
		$p4 = new LinePlot($datay_bp2,$datax_bp2);
		$p4->SetLegend("最低血圧(mmHg)");
		$p4->mark->SetType(MARK_DIAMOND);
		$p4->mark->SetFillColor("orange");
		$p4->mark->SetWidth(4);
		$p4->SetColor('orange');
		$graph->Add($p4);
		
		$graph->Stroke();
		
		
?>