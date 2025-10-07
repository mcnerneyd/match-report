import os
from typing import Annotated, Literal
from fastapi import FastAPI, Query, Depends, HTTPException, APIRouter
from pydantic import BaseModel
from sqlalchemy.orm import Session
from sqlalchemy.sql import text
from db import get_db

import json

fixtures_last = False
fixtures_all = {}

def all_fixtures():
    global fixtures_last, fixtures_all
    file_path = "/data/fixtures.json"
    last_modified = os.path.getmtime(file_path)
    if fixtures_last != last_modified:
        with open('/data/fixtures.json', 'r') as f:
            fixtures_raw = [f for f in json.load(f) if f['status'] == 'active']

        fixtures_all = {}
        for f in fixtures_raw:
            fId = int(f.pop('fixtureID'))
            f.pop('status')
            if f['played'] == 'no':
                f.pop('home_score')
                f.pop('away_score')
            else:
                f['home_score'] = int(f.pop('home_score'))
                f['away_score'] = int(f.pop('away_score'))
            f.pop('played')
            fixtures_all[fId] = f
            
        fixtures_last = last_modified
    return fixtures_all

class FixtureParam(BaseModel):
    section: str = ""
    club: str = ""
    competition: str = ""
    def filter(self, fixture):
        if self.section != "" and fixture['section'] != self.section:
            return False
        if self.competition != "" and fixture['competition'] != self.competition:
            return False
        if self.club != "" and fixture['home_club'] != self.club and fixture['away_club'] != self.club:
            return False
        return True

router = APIRouter(prefix="/fixtures")

@router.get("/")
def fixtures(filter: Annotated[FixtureParam, Query()]):
    selection = { f:v for f,v in all_fixtures().items() if filter.filter(v) }
        
    return {"status": "ok", "data": selection}

@router.get("/{item_id}")
def fixtures(item_id: int, db: Session = Depends(get_db)):
    if not item_id in all_fixtures():
        raise HTTPException(status_code=404, detail=f"Fixture not found: {item_id}")
    
    selection = all_fixtures()[item_id]

    matchcard = db.execute(text("""SELECT m.id, h.club_id AS home_club_id, a.club_id AS away_club_id 
                           FROM matchcard m
                           JOIN team h ON h.id = home_id
                           JOIN team a ON a.id = away_id
                           WHERE fixture_id = :id"""), \
                           {"id":item_id}).mappings().first()
    incidents = db.execute(text("""SELECT type, date, player, detail, club_id FROM incident WHERE matchcard_id = :matchcard_id"""), \
                           {"matchcard_id":matchcard['id']}).mappings().all()
    incidents = [dict(row) for row in incidents]

    for i in incidents:       
       if (i['detail'] is None):
           i.pop('detail', None)

    selection["card_id"] = matchcard['id']
    selection["incidents"] = incidents

    return {"status": "ok", "data": selection}

