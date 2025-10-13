import os
from typing import Annotated, Literal
from fastapi import FastAPI, Query, Depends, HTTPException, APIRouter
from pydantic import BaseModel
from sqlalchemy.orm import Session
from sqlalchemy import text
from db import get_db

router = APIRouter()

@router.get("/competitions") #, response_model=Competition)
def competitions(db: Session = Depends(get_db)):
    rows = db.execute(text('''SELECT x.name, s.name section, x.teamsize, x.format
                                FROM competition x JOIN section s ON x.section_id = s.id'''))

    return {"status": "ok", "data": rows.mappings().all() }

@router.get("/clubs")
def competitions(db: Session = Depends(get_db)):
    rows = db.execute(text("SELECT c.name FROM club c"))
    names = [row.name for row in rows.mappings().all()]  
    return {"status": "ok", "data": names }

