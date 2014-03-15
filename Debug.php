<?php

function AddDebug($String, $File="", $Line=0)
{
	if(class_exists("BasicPage"))
		BasicPage::Singleton()->AddDebug($String, $File, $Line);
}


?>