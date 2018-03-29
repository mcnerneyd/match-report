*** Settings ***
Documentation     An example resource file
Library           Selenium2Library
Library						RequestsLibrary

*** Variables ***
${HOST}           cards.leinsterhockey.ie/cards/fuel/public
${LOGIN URL}      http://${HOST}/Login
${WELCOME URL}    http://${HOST}/welcome.html
${BROWSER}        Chrome

*** Keywords ***
Login
	[Arguments]    ${site} 	${username} 	${pin}
	Open Browser    					${LOGIN URL}    ${BROWSER}
	Click Element							xpath=//a[@data-site='${site}']
	Select From List By Label	user-select		${username}
	Input Text    						name=pin    ${pin}
	Click Element							xpath=//form[@id='login']/button
	Element Should Contain		user	${username}

Select Player
	[Arguments]			${player}
	${name}=				Get Element Attribute		xpath=//tr[contains(@data-name,'${player}')]@data-name
	Execute Javascript		window.jQuery("[data-name='${name}']")[0].scrollIntoView(true);
	Execute Javascript		window.scrollBy(0, -150);
	Sleep						2s
	Click Element		jquery=[data-name='${name}']

Check Player
	[Arguments]			${player}		${class}
	${attr}					Get Element Attribute		xpath=//tr[contains(@data-name,'${player}')]@class
	Should Be Equal	${attr}		${class}		Player ${player} not ${class}

Go To Matches
	Go To						http://${HOST}
	Click Element		link=Matches

Submit Team
	Sleep														2 seconds
	Execute Javascript							scrollTo(0,0)
	Click Element										partial link=Submit Team
	Wait Until Element Is Visible		matchcard-home
	Comment													Selecting Players

Submit Card
	[Arguments]			${umpire}		${score}
	Execute Javascript							scrollTo(0,0)
	Sleep						6 seconds
	Input Text			umpire-box		${umpire}
	Input Text			score-box			${score}
	Click Element		jquery=#submit-form .btn-success

Reset Card	
	[Arguments]			${fixture}
	Go To Matches
	${fixtureid}=		Get Element Attribute		jquery=[data-key='${fixture}']@data-id
	${auth}=				Create List		Aardvarks		1234
	Create Session	cards		http://${HOST}	auth=${auth}
	Delete Request	cards		/cards/${fixtureid}?site=test

Open Card
	[Arguments]			${cardkey}
	Go To Matches
	Click Element		xpath=//tr[@data-key='${cardkey}']

Player Menu
	[Arguments]			${player}		${menuitem}
	Execute Javascript							scrollTo(0,280)
	Click Element		xpath=//figure[(contains(@title,'${player}'))]
	Click Element		partial link=${menuitem}

