from typing import NamedTuple


class ListingFilter(NamedTuple):
    name: str
    possibleValues: list[str]
