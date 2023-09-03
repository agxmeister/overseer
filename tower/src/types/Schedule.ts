export type Schedule = {
    key: string,
    estimatedBeginDate?: string,
    estimatedEndDate?: string,
}

export enum Mode {
    View = "view",
    Edit = "edit",
}
