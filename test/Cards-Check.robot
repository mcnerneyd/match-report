*** Settings ***
Resource	Common.robot

*** Test Cases ***
Card Holds Players
	Login						test	Bears		1234
	Reset Card			D1Aardvarks1Bears2

	# Build team
	Open Card				D1Aardvarks1Bears2
	Select Player		DEALBA
	Select Player		DENG
	Select Player		BURRUS
	Select Player		FERRELL
	Select Player		GRISWOLD
	Select Player		ING
	Select Player		LOMAX
	Select Player		LOO
	Select Player		RUNKLE
	Sleep						2 seconds
	Go To Matches

	Open Card				D1Aardvarks1Bears2
	Check Player		DEALBA	selected
	Check Player		DENG	selected
	Check Player		BURRUS	selected
	Check Player		FERRELL	selected
	Check Player		GRISWOLD	selected
	Check Player		ING	selected
	Check Player		LOMAX	selected
	Check Player		LOO	selected
	Check Player		RUNKLE	selected
	Close Browser

