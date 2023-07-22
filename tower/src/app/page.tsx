'use client'

import useSWR from 'swr'
import Map from "@/components/Map/Map";
import Card from "@/components/Card/Card";
import Trace from "@/components/Trace/Trace";
import Task from "@/components/Task/Task";

type Issue = {
    key: string,
    estimatedStartDate: string,
    estimatedFinishDate: string,
    summary: string,
}

export default function Page() {
    const {data} = useSWR('http://localhost:8080/api/v1/hello', (api: string) => fetch(api).then(res => res.json()));
    const tasks = data ? data.map((issue: Issue) =>
        <Task id={issue.key} trace={
            <Trace
                id={issue.key}
                start={issue.estimatedStartDate}
                finish={issue.estimatedFinishDate}
            />
        } card={
            <Card
                key={issue.key}
                id={issue.key}
                start={issue.estimatedStartDate}
                finish={issue.estimatedFinishDate}
                title={issue.summary}
            />
        }/>
    ): [];
    return <Map tasks={tasks}/>
}
