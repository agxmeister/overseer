export type Link = {
    id: number,
    key: string,
    type: string,
}

export enum Type {
    Depends = "Depends",
    Follows = "Follows",
}
