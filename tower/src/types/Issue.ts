import {IssueLink} from "@/types/IssueLink";

export type Issue = {
    key: string,
    summary?: string,
    begin?: string,
    end?: string,
    links?: {inward?: IssueLink[], outward?: IssueLink[]},
    corrected?: boolean,
}
