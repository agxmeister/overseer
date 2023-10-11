export type Link = {
    from: string,
    to: string,
    type: string,
}

export enum Type {
    Sequence = "sequence",
    Schedule = "schedule",
}
