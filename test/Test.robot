*** Settings ***
Resource	Common.robot

*** Test Cases ***
Create A Card
	Login						test	Aardvarks		1234
	Reset Card			D1Aardvarks1Bears2

	# Build team
	Open Card				D1Aardvarks1Bears2
	Select Player		AIGNER
