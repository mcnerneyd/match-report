from pydantic import BaseModel, Optional, Tuple
from datetime import datetime

class Club(BaseModel):
    id: int
    name: str

class Section(BaseModel):
    id: int
    name: str
    active: bool

class CompetitionFormat(str, Enum):
    league = 'league'
    cup = 'cup'

class Competition(BaseModel):
    id: int
    name: str
    section: Section
    teamsize: Optional[int]
    teamstars: Optional[int]
    format: CompetitionFormat = CompetitionFormat.league
    groups: Optional[list]

class Team(BaseModel):
    id: int
    club: Club
    name: str
    section: Section

class Entry(BaseModel):
    team: Team
    competition: Competition

class Fixture(BaseModel):
    id: int
    section: Section
    competition: Competition
    date: datetime
    home: Team
    away: Team
    score: Optional[Tuple[int, int]] = None
    active: bool

class Matchcard(BaseModel):
    id: int
    fixture: Fixture
    date: datetime
    status: str
