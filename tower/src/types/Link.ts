export type Link = {
    id: number,
    key: string,
    type: string,
}

export enum Type {
    Sequence = "sequence",
    Schedule = "schedule",
}
