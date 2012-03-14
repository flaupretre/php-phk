<?php
//=============================================================================
//
// Copyright Francois Laupretre <phk@tekwire.net>
//
//   Licensed under the Apache License, Version 2.0 (the "License");
//   you may not use this file except in compliance with the License.
//   You may obtain a copy of the License at
//
//       http://www.apache.org/licenses/LICENSE-2.0
//
//   Unless required by applicable law or agreed to in writing, software
//   distributed under the License is distributed on an "AS IS" BASIS,
//   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//   See the License for the specific language governing permissions and
//   limitations under the License.
//
//=============================================================================

if (!class_exists('PHK',0))
	{
	//-- When extension is not present, the first package loads the PHP
	//-- ('slow') runtime code.

	$_phk_fp=fopen(__FILE__,'rb');
	$_phk_buf=fread($_phk_fp,241);
	fseek($_phk_fp,(int)(substr($_phk_buf,212,11)),SEEK_SET);
	$_phk_size=(int)(substr($_phk_buf,227,11));

	$_phk_code='';
	while (strlen($_phk_code) < $_phk_size)
		$_phk_code .=fread($_phk_fp,$_phk_size-strlen($_phk_code));

	fclose($_phk_fp);

	eval($_phk_code);

	unset($_phk_code);
	unset($_phk_fp);
	unset($_phk_buf);
	unset($_phk_size);
	}

//------

$_phk_cmd=null;
$_phk_ret=0;

PHK::prolog(__FILE__,$_phk_cmd,$_phk_ret);

eval($_phk_cmd);

//var_dump($_phk_cmd);
//var_dump($_phk_ret);

return $_phk_ret;
?>
