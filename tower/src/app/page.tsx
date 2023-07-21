'use client'

import useSWR from 'swr'
import Map from "@/components/Map/Map";
import Card from "@/components/Card/Card";

type Issue = {
    key: string,
    estimatedStartDate: string,
    estimatedFinishDate: string,
    summary: string,
}

export default function Page() {
    const {data} = useSWR('http://localhost:8080/api/v1/hello', (api: string) => fetch(api).then(res => res.json()));
    const cards = data ? data.map((issue: Issue) =>
        <Card
            key={issue.key}
            id={issue.key}
            start={issue.estimatedStartDate}
            finish={issue.estimatedFinishDate}
            title={issue.summary}
        ></Card>
    ): [];
    return <Map cards={cards}/>
}
