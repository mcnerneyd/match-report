*** Settings ***
Resource	Common.robot

*** Test Cases ***
Logout
	Login		test	Aardvarks	1111
	Click Element		partial link=Logout
	Element Text Should Be		tag=h2	Test Site
	Click Element		link=Change Site
	Click Element		link=Leinster Mens Hockey
	Element Text Should Be		tag=h2	LHA Mens Section
	Click Element		link=Change Site
	Click Element		link=Test Site
	Element Text Should Be		tag=h2	Test Site
	

