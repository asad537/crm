<?php
// Run on the server:  php artisan tinker docs/email-system/reports/portal-password-and-card-requests.php
$agents = array_flip(App\CrmMailAccount::withoutGlobalScopes()->pluck("email_address")->map(fn($e)=>strtolower($e))->all());
$clean = fn($m) => preg_replace("/<mailto:[^>]+>/","", preg_replace("/\s+/"," ", html_entity_decode(strip_tags($m->text_body ?: ($m->html_body ?? "")))));
echo "=== LIST 1: portal password 123456 ===\n";
$q = App\CrmMailMessage::withoutGlobalScopes()->select(["id","account_id","subject","from_email","to_json","text_body","html_body","sent_at","received_at"])->where(fn($w)=>$w->where("text_body","like","%123456%")->orWhere("html_body","like","%123456%"))->orderByRaw("COALESCE(sent_at, received_at)");
$out=[]; foreach ($q->lazy(200) as $m) { $body=str_replace("*","",$clean($m));
  if (!preg_match_all("/(?:Username|Email|User ?name|Login)\s*:\s*<?([\w.+-]+@[\w.-]+\.\w+)>?[^@]{0,80}?Password\s*:\s*123456/i", $body, $mm)) continue;
  $date=substr((string)($m->sent_at ?: $m->received_at),0,10); $isAgent=isset($agents[strtolower($m->from_email)]);
  $agent=$isAgent ? strtolower($m->from_email) : (preg_match("/Regards,?\s*([A-Z][a-z]+ [A-Z][a-z]+)/",$body,$rm) ? $rm[1]." (quoted)" : "(reply to ".App\CrmMailAccount::withoutGlobalScopes()->find($m->account_id)->email_address.")");
  foreach (array_unique(array_map("strtolower",$mm[1])) as $login) { if (isset($agents[$login])) continue; $o=&$out[$login]; if(!$o) $o=["login"=>$login,"agent"=>$agent,"first"=>$date,"last"=>$date,"times"=>0]; $o["last"]=$date; $o["times"]++; if($isAgent) $o["agent"]=$agent; if($o["first"]>$date) $o["first"]=$date; unset($o); } }
ksort($out); foreach ($out as $r) echo $r["login"], " | ", $r["agent"], " | ", $r["first"], " | ", $r["last"], " | ", $r["times"], "\n"; echo "TOTAL customers=", count($out), "\n";
echo "\n=== LIST 2: card info requests (agent-sent) ===\n";
$q = App\CrmMailMessage::withoutGlobalScopes()->select(["id","account_id","subject","from_email","to_json","text_body","html_body","sent_at","received_at"])->whereIn(DB::raw("LOWER(from_email)"), array_keys($agents))->where(fn($w)=>$w->where("text_body","like","%card%")->orWhere("html_body","like","%card%"))->orderByRaw("COALESCE(sent_at, received_at)");
$out=[]; $n=0; foreach ($q->lazy(100) as $m) { $n++; $body=$clean($m); $top=preg_split("/(?:\bOn .{10,90}wrote:|-----Original Message-----|\bFrom: [^|]{3,80}Sent: )/u",$body)[0] ?? $body;
  if (!preg_match("/.{0,140}(card (?:details|detail|info|information|number)|credit card|debit card|cvv|cvc|card ?holder|expiry|expiration date|billing zip|card on file|(?:share|send|provide|email|forward)[^.]{0,30}\bcard).{0,140}/i",$top,$cm)) continue;
  $to=collect(is_array($m->to_json)?$m->to_json:[])->map(fn($t)=>strtolower(is_array($t)?($t["email"]??""):$t))->filter(fn($e)=>!isset($agents[$e]))->implode(",");
  $k=strtolower($m->from_email)."|".$to; $o=&$out[$k]; if(!$o) $o=["agent"=>strtolower($m->from_email),"customer"=>$to,"first"=>substr((string)($m->sent_at?:$m->received_at),0,10),"count"=>0,"subject"=>mb_substr($m->subject,0,45),"ctx"=>trim($cm[0])]; $o["count"]++; unset($o); }
usort($out, fn($a,$b)=>[$a["agent"],$a["first"]]<=>[$b["agent"],$b["first"]]);
foreach ($out as $r) echo $r["agent"], " -> ", $r["customer"], " | ", $r["first"], " | x", $r["count"], " | ", $r["subject"], "\n    \"", mb_substr($r["ctx"],0,240), "\"\n";
echo "scanned=$n matches=", count($out), "\n";
