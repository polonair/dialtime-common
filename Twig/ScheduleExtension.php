<?php

namespace Polonairs\Dialtime\CommonBundle\Twig;

use Polonairs\Dialtime\CommonBundle\Entity\Schedule;

class ScheduleExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return [ new \Twig_SimpleFilter('schedule', [$this, 'scheduleFilter']) ];
    }
    public function scheduleFilter(Schedule $schedule)
    {
    	if ($schedule->getIntervals() === null) return "";

    	$intervals = [];
    	$days = [];
    	foreach ($schedule->getIntervals()->toArray() as $i) 
    		$intervals[] = ["from" => $i->getFrom(), "to" => $i->getTo()];

    	$resampled = [];
    	foreach($intervals as $i)
    	{
    		$a = $i;
    		while(true)
    		{
	    		$from_day = floor($a["from"]/1440);
	    		$to_day = floor($a["to"]/1440);
    			if ($from_day == $to_day)
    			{
    				$resampled[] = $a;
    				break;
    			}
    			else
    			{
    				$t = [];
    				$t["from"] = $a["from"];
    				$t["to"] = ($from_day+1)*1440-1;
    				$a["from"] = $t["to"]+1;
    				$resampled[] = $t;
    			}
    		}
    	}
    	//dump($resampled);

    	$bydays = [];

    	foreach($resampled as $r) $bydays[floor($r["from"]/1440)][] = ["from" => $r["from"]%1440, "to" => $r["to"]%1440];
    	//dump($bydays);

    	$merged = [];

    	foreach($bydays as $day => $int)
    	{
    		$merged[$day] = $this->merge($int);
    	}

    	$out = [];

    	for ($i = 0; $i < 7; $i++)
    	{
    		if (array_key_exists($i, $merged))
    		{
	    		$out[$i] = $merged[$i];
	    	}
	    	else
	    	{
	    		$out[$i] = [];
	    	}
    	}
    	//dump($merged);
    	//dump($out);

    	$last_stage = [];

    	for ($i = 0; $i<7; $i++)
    	{
    		$cur = $i;
    		$last = $i;
    		for($j = $i+1; $j<7; $j++)
    		{
    			if($this->eq($out[$i], $out[$j])) $last = $j;
    			else break;
    		}
			$last_stage[] = ["df" => $cur, "dt" => $last, "ints" =>$out[$cur]];
			$i = $last;
    	}

    	$result = "";
    	$dw = ["ПН","ВТ","СР","ЧТ","ПТ","СБ","ВС"];

    	foreach ($last_stage as $value) 
    	{
    		if (count($value["ints"])>0)
    		{
	    		if ($value["df"] == $value["dt"])
	    		{
	    			$result .= $dw[$value["df"]].": ";
	    		}
	    		else
	    		{
	    			$result .= $dw[$value["df"]]."-".$dw[$value["dt"]].": ";
	    		}
	    		foreach ($value["ints"] as $time) 
	    		{
	    			//$result.=date("H:i", $time["from"]*60)."-".date("H:i", ($time["to"]+1)*60).", ";
	    			$result.=sprintf("%'.02d:%'.02d - %'.02d:%'.02d, ",
	    				floor($time["from"]/60),
	    				$time["from"]%60,
	    				floor(($time["to"]+1)/60),
	    				($time["to"]+1)%60);
	    		}
	    		$result = substr($result, 0, -2);
	    		$result .= "; ";
	    	}
    	}
    	$result = substr($result, 0, -1);

    	return $result;
    }
    private function eq($a, $b)
    {
    	if (count($a)!=count($b)) return false;
    	if (count($a)==0) return false;
    	for($i = 0; $i < count($a); $i++)
    	{
    		if ($a[$i]["from"] != $b[$i]["from"] || $a[$i]["to"] != $b[$i]["to"]) 
    			return false;
    	}
    	return true;
    }
    private function merge($intervals)
    {
    	//dump($intervals);
    	usort($intervals, function ($a, $b){ return $a["from"] - $b["from"]; });
    	//dump($intervals);
    	while(true)
    	{
    		$stop = true;
    		$prev = null;
    		foreach($intervals as $key => $val)
    		{
    			if ($prev === null)
    			{
    				$prev = $key;
    			}
    			else
    			{
    				if (($intervals[$prev]["to"]+1)>=$intervals[$key]["from"])
    				{
	    				$intervals[$key]["from"] = $intervals[$prev]["from"];
	    				unset($intervals[$prev]);
	    				//dump($intervals);
	    				$stop = false;
	    				break;
    				}
    				else
    				{
    					$prev = $key;
    				}
    			}
    		}
    		if ($stop) break;
    	}
    	//dump($intervals);
    	return $intervals;
    }
    public function getName()
    {
        return 'schedule_extension';
    }
}